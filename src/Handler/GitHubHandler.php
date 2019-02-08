<?php

namespace App\Handler;

use App\Event\LabelsAppliedEvent;
use App\Event\PullRequestMergedEvent;
use App\Event\PullRequestMergeFailureEvent;
use App\Model\PullRequest;
use Github\Client as GitHubClient;
use JiraRestApi\Issue\Issue as JiraIssue;
use JiraRestApi\JiraException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GitHubHandler
{
    const CHANGES_REQUESTED = 'CHANGES_REQUESTED';
    const APPROVED          = 'APPROVED';

    /** @var GitHubClient */
    private $gitHubClient;

    /** @var JiraHandler */
    private $jiraHandler;

    /** @var EventDispatcherInterface $eventDispatcher */
    protected $eventDispatcher;

    public function __construct(JiraHandler $jiraHandler, EventDispatcherInterface $eventDispatcher)
    {
        $this->gitHubClient = new GitHubClient();
        $this->gitHubClient->authenticate(getenv('GITHUB_TOKEN'), null, GitHubClient::AUTH_HTTP_TOKEN);
        $this->jiraHandler  = $jiraHandler;
        $this->eventDispatcher  = $eventDispatcher;
    }

    public static function arraysToPullRequests(array $pullRequestsData)
    {
        $pullRequests = [];

        foreach ($pullRequestsData as $pullRequestData) {
            $pullRequests[] = new PullRequest($pullRequestData);
        }

        return $pullRequests;
    }

    public function setReviewsOfPullRequest(PullRequest &$pullRequest)
    {
        $pullRequest->setReviews(array_reverse($this->getPullRequestReviews($pullRequest)));
    }

    public function getOpenPullRequests(array $filters = []): array
    {
        $pullRequestsData = $this->gitHubClient->api('pull_request')->all(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            ['state' => 'open', 'per_page' => 50] + $filters
        );

        return self::arraysToPullRequests($pullRequestsData);
    }

    public function getOpenPullRequestsWithLabel(string $label)
    {
        $pullRequestsData = $this->gitHubClient->api('issue')->all(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            [
                'state'    => 'open',
                'per_page' => 50,
                'labels'   => $label,
            ]
        );

        return self::arraysToPullRequests($pullRequestsData);
    }

    public function getOpenPullRequestFromHeadBranch(string $headBranchName)
    {
        $pullRequests = $this->getOpenPullRequests();

        /** @var PullRequest $pullRequest */
        foreach ($pullRequests as $pullRequest) {
            $isHeadMatching = strtoupper($pullRequest->getHeadRef()) === strtoupper($headBranchName);
            $isBaseDefault  = strtoupper($pullRequest->getBaseRef()) === strtoupper(getenv('GITHUB_DEFAULT_BASE_BRANCH'));

            if ($isHeadMatching && $isBaseDefault) {
                return $pullRequest;
            }
        }

        return null;
    }

    public function getOpenPullRequestFromJiraIssueKey(string $jiraIssueName)
    {
        $pullRequests = $this->getOpenPullRequests();

        /** @var PullRequest $pullRequest */
        foreach ($pullRequests as $pullRequest) {
            if (false !== strpos(strtoupper($pullRequest->getHeadRef()), strtoupper($jiraIssueName))) {
                return $pullRequest;
            }
        }

        /** @var PullRequest $pullRequest */
        foreach ($pullRequests as $pullRequest) {
            if (false !== strpos(strtoupper($pullRequest->getTitle()), strtoupper($jiraIssueName))) {
                return $pullRequest;
            }
        }

        return null;
    }

    public function getJiraIssueFromPullRequest(PullRequest $pullRequest): ?JiraIssue
    {
        $jiraIssueKey = JiraHandler::extractIssueKeyFromString($pullRequest->getHeadRef())
            ?? JiraHandler::extractIssueKeyFromString($pullRequest->getTitle());

        if (null === $jiraIssueKey) {
            return null;
        }

        try {
            return $this->jiraHandler->getIssue($jiraIssueKey);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getPullRequest(int $pullRequestNumber): PullRequest
    {
        $pullRequestData = $this->gitHubClient->api('pull_request')->show(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            $pullRequestNumber
        );

        return new PullRequest($pullRequestData);
    }

    public function addLabelToPullRequest(string $label, PullRequest $pullRequest)
    {
        $this->gitHubClient->api('issue')->labels()->add(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            $pullRequest->getNumber(),
            $label
        );
    }

    public function removeLabelFromPullRequest(string $label, PullRequest $pullRequest)
    {
        $this->gitHubClient->api('issue')->labels()->remove(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            $pullRequest->getNumber(),
            $label
        );
    }

    public function getPullRequestReviews(PullRequest $pullRequest)
    {
        return $this->gitHubClient->api('pull_request')->reviews()->all(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            $pullRequest->getNumber()
        );
    }

    public function mergePullRequest(PullRequest $pullRequest, string $mergeMethod = 'merge')
    {
        try {
            $this->gitHubClient->api('pull_request')->merge(
                getenv('GITHUB_REPOSITORY_OWNER'),
                getenv('GITHUB_REPOSITORY_NAME'),
                $pullRequest->getNumber(),
                $pullRequest->getTitle(),
                $pullRequest->getHeadSha(),
                $mergeMethod
            );
        } catch (\Exception $e) {
            $this->eventDispatcher->dispatch(PullRequestMergeFailureEvent::NAME, new PullRequestMergeFailureEvent($pullRequest, $e->getMessage()));
        }

        $this->eventDispatcher->dispatch(PullRequestMergedEvent::NAME, new PullRequestMergedEvent($pullRequest));
    }

    public function isBranchIgnored(string $branchName): bool
    {
        $branchName         = strtoupper($branchName);
        $explodedBranchName = explode('/', $branchName);
        $ignoredBranches    = explode(',', getenv('GITHUB_IGNORED_PREFIXES'));

        return \in_array($explodedBranchName[0], $ignoredBranches);
    }

    public function isPullRequestApproved(PullRequest $pullRequest): bool
    {
        if (empty($pullRequest->getReviews())) {
            $this->setReviewsOfPullRequest($pullRequest);
        }

        $approveCount = 0;

        foreach ($pullRequest->getReviews() as $review) {
            if (self::CHANGES_REQUESTED === $review['state']) {
                return false;
            }

            if (self::APPROVED === $review['state']) {
                ++$approveCount;

                if ($approveCount >= getenv('GITHUB_APPROVE_COUNT')) {
                    return true;
                }
            }
        }

        return false;
    }

    public function doesReviewBranchExists(string $reviewBranchName)
    {
        return \in_array(
            getenv('GITHUB_REVIEW_ENVIRONMENT_PREFIX') . $reviewBranchName,
            explode(',', getenv('GITHUB_REVIEW_LABELS'))
        );
    }

    public function isReviewBranchAvailable(string $reviewBranchName, PullRequest $pullRequest)
    {
        $pullRequests = $this->getOpenPullRequestsWithLabel(getenv('GITHUB_REVIEW_ENVIRONMENT_PREFIX') . $reviewBranchName);

        return 0 === \count($pullRequests)
            || (1 === \count($pullRequests) && $pullRequests[0]->getNumber() === $pullRequest->getNumber());
    }

    public function checkDeployability(string $headBranchName, string $reviewBranchName, ?PullRequest $pullRequest = null, bool $force = false)
    {
        if ($headBranchName === getenv('GITHUB_DEFAULT_BASE_BRANCH')) {
            return 'OK';
        }

        if (null === $pullRequest) {
            $pullRequest = $this->getOpenPullRequestFromHeadBranch($headBranchName);
        }

        if (null === $pullRequest) {
            echo 'Pull Request not found.';

            die;
        }

        if (
            true === $force
            && $this->hasLabel($pullRequest, getenv('GITHUB_FORCE_LABEL'))
            && $this->isReviewBranchAvailable($reviewBranchName, $pullRequest)
        ) {
            return 'OK';
        }

        if (empty($pullRequest) || null === $pullRequest) {
            echo 'We have not found any pull request with head branch "' . $headBranchName . '".';

            die;
        }

        if (!$this->doesReviewBranchExists($reviewBranchName)) {
            echo 'The review branch "' . $reviewBranchName . '" does not exist or does not have any attributed label.';

            die;
        }

        if (!$this->isReviewBranchAvailable($reviewBranchName, $pullRequest)) {
            echo 'The review branch "' . $reviewBranchName . '" is already used by another PR.';

            die;
        }

        if (!$this->isPullRequestApproved($pullRequest)) {
            echo 'The pull request with head branch "' . $headBranchName . '" does not have enough approving reviews or has requested changes.';

            die;
        }

        return 'OK';
    }

    public function removeReviewLabels(PullRequest $pullRequest)
    {
        $reviewLabels   = explode(',', getenv('GITHUB_REVIEW_LABELS'));
        $reviewLabels[] = getenv('GITHUB_REVIEW_REQUIRED_LABEL');
        $reviewLabels[] = getenv('GITHUB_FORCE_LABEL');

        foreach ($reviewLabels as $reviewLabel) {
            if ($this->hasLabel($pullRequest, $reviewLabel)) {
                $this->removeLabelFromPullRequest($reviewLabel, $pullRequest);
            }
        }
    }

    public function hasLabel(PullRequest $pullRequest, string $search)
    {
        return \in_array($search, $pullRequest->getLabels());
    }

    public function isDeployed(PullRequest $pullRequest): bool
    {
        $reviewLabels = explode(',', getenv('GITHUB_REVIEW_LABELS'));

        foreach ($reviewLabels as $reviewLabel) {
            if ($this->hasLabel($pullRequest, $reviewLabel)) {
                return true;
            }
        }

        return false;
    }

    public function isValidated(PullRequest $pullRequest): bool
    {
        return $this->hasLabel($pullRequest, getenv('GITHUB_REVIEW_OK_LABEL'));
    }

    /**
     * @throws JiraException
     */
    public function applyLabels(string $headBranchName, string $reviewBranchName, bool $force = false): bool
    {
        $pullRequest = $this->getOpenPullRequestFromHeadBranch($headBranchName);

        if ('OK' !== $this->checkDeployability($headBranchName, $reviewBranchName, $pullRequest, $force)) {
            return false;
        }

        $this->removeReviewLabels($pullRequest);
        $this->addLabelToPullRequest(getenv('GITHUB_REVIEW_ENVIRONMENT_PREFIX') . $reviewBranchName, $pullRequest);

        $jiraIssueKey = JiraHandler::extractIssueKeyFromString($headBranchName)
            ?? JiraHandler::extractIssueKeyFromString($pullRequest->getTitle());

        if (null !== $jiraIssueKey) {
            $this->jiraHandler->transitionIssueTo($jiraIssueKey, getenv('JIRA_TRANSITION_ID_TO_VALIDATE'));
        }

        $this->eventDispatcher->dispatch(
            LabelsAppliedEvent::NAME,
            new LabelsAppliedEvent($pullRequest, $reviewBranchName, $jiraIssueKey)
        );

        return true;
    }

    /**
     * @throws JiraException
     */
    public function handleReviewRequiredLabel(PullRequest $pullRequest, JiraIssue $jiraIssue)
    {
        if (
            $this->hasLabel($pullRequest, getenv('GITHUB_REVIEW_REQUIRED_LABEL'))
            && (
                $this->isBranchIgnored($pullRequest->getHeadRef())
                || !$this->isPullRequestApproved($pullRequest)
                || $this->isDeployed($pullRequest)
                || $this->isValidated($pullRequest)
            )
        ) {
            $this->removeLabelFromPullRequest(getenv('GITHUB_REVIEW_REQUIRED_LABEL'), $pullRequest);
        }

        if (
            !$this->hasLabel($pullRequest, getenv('GITHUB_REVIEW_REQUIRED_LABEL'))
            && !$this->isBranchIgnored($pullRequest->getHeadRef())
            && $this->isPullRequestApproved($pullRequest)
            && !$this->isDeployed($pullRequest)
            && !$this->isValidated($pullRequest)
        ) {
            $this->addLabelToPullRequest(getenv('GITHUB_REVIEW_REQUIRED_LABEL'), $pullRequest);

            if ($jiraIssue->fields->status->name !== getenv('JIRA_STATUS_TO_VALIDATE')) {
                $this->jiraHandler->transitionIssueTo($jiraIssue->key, getenv('JIRA_TRANSITION_ID_TO_VALIDATE'));
            }
        }
    }

    /**
     * @throws JiraException
     */
    public function handleInProgressPullRequest(PullRequest $pullRequest, JiraIssue $jiraIssue)
    {
        $labels = explode(',', getenv('GITHUB_IN_PROGRESS_LABELS'));

        foreach ($labels as $label) {
            if ($this->hasLabel($pullRequest, $label)) {
                if ($jiraIssue->fields->status->name !== getenv('JIRA_STATUS_IN_PROGRESS')) {
                    $this->jiraHandler->transitionIssueTo($jiraIssue->key, getenv('JIRA_TRANSITION_ID_IN_PROGRESS'));
                }

                return true;
            }
        }

        return false;
    }

    public function addJiraLinkToDescription(PullRequest $pullRequest, JiraIssue $jiraIssue)
    {
        $pullRequestBody = $pullRequest->getBody();
        $jiraIssueUrl    = JiraHandler::buildIssueUrlFromIssueName($jiraIssue->key);

        if (false === \strpos($pullRequestBody, $jiraIssueUrl)) {
            $this->updatePullRequestBody($pullRequest, $jiraIssueUrl . "\n\n" . $pullRequestBody);
        }
    }

    public function updatePullRequestBody(PullRequest $pullRequest, string $body)
    {
        $pullRequestData = $this->gitHubClient->api('pull_request')->update(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            $pullRequest->getNumber(),
            ['body' => $body]
        );

        return new PullRequest($pullRequestData);
    }

    /**
     * @throws JiraException
     */
    public function synchronize()
    {
        $pullRequests = $this->getOpenPullRequests();

        /** @var PullRequest $pullRequest */
        foreach ($pullRequests as $pullRequest) {
            $jiraIssue = $this->getJiraIssueFromPullRequest($pullRequest);

            if (null === $jiraIssue) {
                continue;
            }

            $this->addJiraLinkToDescription($pullRequest, $jiraIssue);

            if ($jiraIssue->fields->status->name === getenv('JIRA_STATUS_BLOCKED')) {
                continue;
            }

            $this->handleReviewRequiredLabel($pullRequest, $jiraIssue);

            if (false === $this->handleInProgressPullRequest($pullRequest, $jiraIssue)) {
                if (false === $this->isPullRequestApproved($pullRequest)) {
                    if ($jiraIssue->fields->status->name !== getenv('JIRA_STATUS_TO_REVIEW')) {
                        $this->jiraHandler->transitionIssueTo($jiraIssue->key, getenv('JIRA_TRANSITION_ID_TO_REVIEW'));
                    }
                }
            }
        }
    }
}
