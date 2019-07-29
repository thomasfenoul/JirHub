<?php

namespace App\Handler;

use App\Dashboard\Handler\DashboardHandler;
use App\Event\LabelsAppliedEvent;
use App\Factory\PullRequestFactory;
use App\Helper\JiraHelper;
use App\Model\PullRequest;
use App\Model\PullRequestReview;
use App\Repository\GitHub\PullRequestLabelRepository;
use App\Repository\GitHub\PullRequestRepository;
use App\Repository\GitHub\PullRequestReviewRepository;
use App\Repository\GitHub\PullRequestSearchFilters;
use App\Repository\Jira\JiraIssueRepository;
use Github\Client as GitHubClient;
use JiraRestApi\Issue\Issue as JiraIssue;
use JiraRestApi\JiraException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GitHubHandler
{
    const CHANGES_REQUESTED = 'CHANGES_REQUESTED';
    const APPROVED          = 'APPROVED';

    /** @var GitHubClient */
    private $gitHubClient;

    /** @var PullRequestRepository */
    private $pullRequestRepository;

    /** @var PullRequestReviewRepository */
    private $pullRequestReviewRepository;

    /** @var PullRequestLabelRepository */
    private $pullRequestLabelRepository;

    /** @var JiraIssueRepository */
    private $jiraIssueRepository;

    /** @var EventDispatcherInterface $eventDispatcher */
    private $eventDispatcher;

    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(
        GithubClient $gitHubClient,
        PullRequestRepository $pullRequestRepository,
        PullRequestReviewRepository $pullRequestReviewRepository,
        PullRequestLabelRepository $pullRequestLabelRepository,
        JiraIssueRepository $jiraIssueRepository,
        EventDispatcherInterface $eventDispatcher,
        CacheItemPoolInterface $cache
    ) {
        $this->gitHubClient                = $gitHubClient;
        $this->pullRequestRepository       = $pullRequestRepository;
        $this->pullRequestReviewRepository = $pullRequestReviewRepository;
        $this->pullRequestLabelRepository  = $pullRequestLabelRepository;
        $this->jiraIssueRepository         = $jiraIssueRepository;
        $this->eventDispatcher             = $eventDispatcher;
        $this->cache                       = $cache;
    }

    public function getOpenPullRequestFromHeadBranch(string $headBranchName)
    {
        $pullRequests = $this->pullRequestRepository->search(
            [PullRequestSearchFilters::HEAD_REF => $headBranchName]
        );

        return (true === empty($pullRequests)) ? null : array_pop($pullRequests);
    }

    public function getOpenPullRequestFromJiraIssueKey(string $jiraIssueName)
    {
        $pullRequests = $this->pullRequestRepository->search();

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
        $jiraIssueKey = JiraHelper::extractIssueKeyFromString($pullRequest->getHeadRef())
            ?? JiraHelper::extractIssueKeyFromString($pullRequest->getTitle());

        if (null === $jiraIssueKey) {
            return null;
        }

        try {
            return $this->jiraIssueRepository->getIssue($jiraIssueKey);
        } catch (\Exception $e) {
            return null;
        }
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
        $approveCount = 0;
        $reviews      = array_reverse($this->pullRequestReviewRepository->search($pullRequest));

        /** @var PullRequestReview $review */
        foreach ($reviews as $review) {
            if (self::CHANGES_REQUESTED === $review->getState()) {
                return false;
            }

            if (self::APPROVED === $review->getState()) {
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
            explode(',', getenv('GITHUB_REVIEW_LABELS')),
            true
        );
    }

    public function isReviewBranchAvailable(string $reviewBranchName, PullRequest $pullRequest)
    {
        $pullRequests = $this->pullRequestRepository->search(
            [
                PullRequestSearchFilters::LABELS => [
                    getenv('GITHUB_REVIEW_ENVIRONMENT_PREFIX') . $reviewBranchName,
                ],
            ]
        );

        $occupiedByTheSamePullRequest = (
            1 === \count($pullRequests)
            && array_pop($pullRequests)->getId() === $pullRequest->getId()
        );

        return 0 === \count($pullRequests)
            || $occupiedByTheSamePullRequest;
    }

    public function checkDeployability(
        string $headBranchName,
        string $reviewBranchName,
        ?PullRequest $pullRequest = null
    ) {
        if ($headBranchName === getenv('GITHUB_DEFAULT_BASE_BRANCH')) {
            return 'OK';
        }

        if (null === $pullRequest) {
            $pullRequest = $this->getOpenPullRequestFromHeadBranch($headBranchName);
        }

        if (null === $pullRequest) {
            return 'Pull Request not found.';
        }

        if ($this->hasLabel($pullRequest, getenv('GITHUB_REVIEW_ENVIRONMENT_PREFIX') . $reviewBranchName)) {
            return 'OK';
        }

        if (empty($pullRequest) || null === $pullRequest) {
            return 'We have not found any pull request with head branch "' . $headBranchName . '".';
        }

        if (!$this->doesReviewBranchExists($reviewBranchName)) {
            return 'The review branch "' . $reviewBranchName . '" does not exist or does not have any attributed label.';
        }

        if (!$this->isReviewBranchAvailable($reviewBranchName, $pullRequest)) {
            return 'The review branch "' . $reviewBranchName . '" is already used by another PR.';
        }

        if (!$this->isPullRequestApproved($pullRequest)) {
            return 'The pull request with head branch "' . $headBranchName . '" does not have enough approving reviews or has requested changes.';
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
                $this->pullRequestLabelRepository->delete(
                    $pullRequest,
                    $reviewLabel
                );
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
    public function applyLabels(string $headBranchName, string $reviewBranchName): bool
    {
        $pullRequest = $this->getOpenPullRequestFromHeadBranch($headBranchName);

        if ('OK' !== $this->checkDeployability($headBranchName, $reviewBranchName, $pullRequest)) {
            return false;
        }

        $this->removeReviewLabels($pullRequest);
        $this->pullRequestLabelRepository->create(
            $pullRequest,
            getenv('GITHUB_REVIEW_ENVIRONMENT_PREFIX') . $reviewBranchName
        );

        $jiraIssueKey = JiraHelper::extractIssueKeyFromString($headBranchName)
            ?? JiraHelper::extractIssueKeyFromString($pullRequest->getTitle());

        if (null !== $jiraIssueKey) {
            $this->jiraIssueRepository->transitionIssueTo($jiraIssueKey, getenv('JIRA_TRANSITION_ID_TO_VALIDATE'));
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
            $this->pullRequestLabelRepository->delete(
                $pullRequest,
                getenv('GITHUB_REVIEW_REQUIRED_LABEL')
            );
        }

        if (
            !$this->hasLabel($pullRequest, getenv('GITHUB_REVIEW_REQUIRED_LABEL'))
            && !$this->isBranchIgnored($pullRequest->getHeadRef())
            && $this->isPullRequestApproved($pullRequest)
            && !$this->isDeployed($pullRequest)
            && !$this->isValidated($pullRequest)
        ) {
            $this->pullRequestLabelRepository->create(
                $pullRequest,
                getenv('GITHUB_REVIEW_REQUIRED_LABEL')
            );

            if ($jiraIssue->fields->status->name !== getenv('JIRA_STATUS_TO_VALIDATE')) {
                $this->jiraIssueRepository->transitionIssueTo($jiraIssue->key, getenv('JIRA_TRANSITION_ID_TO_VALIDATE'));
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
                    $this->jiraIssueRepository->transitionIssueTo($jiraIssue->key, getenv('JIRA_TRANSITION_ID_IN_PROGRESS'));
                }

                return true;
            }
        }

        return false;
    }

    public function addJiraLinkToDescription(PullRequest $pullRequest, JiraIssue $jiraIssue)
    {
        $pullRequestBody = $pullRequest->getBody();
        $jiraIssueUrl    = JiraHelper::buildIssueUrlFromIssueName($jiraIssue->key);

        if (false === strpos($pullRequestBody, $jiraIssueUrl)) {
            $this->updatePullRequestBody($pullRequest, $jiraIssueUrl . "\n\n" . $pullRequestBody);
        }
    }

    public function updatePullRequestBody(PullRequest $pullRequest, string $body)
    {
        $pullRequestData = $this->gitHubClient->pullRequests()->update(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            $pullRequest->getId(),
            ['body' => $body]
        );

        return PullRequestFactory::fromArray($pullRequestData);
    }

    /**
     * @throws InvalidArgumentException
     * @throws JiraException
     */
    public function synchronize(array $webhookData): void
    {
        $this->cache->deleteItem(DashboardHandler::CACHE_KEY);

        $pullRequest = null;

        if (true === \array_key_exists('pull_request', $webhookData)) {
            $pullRequest = $this->pullRequestRepository->fetch($webhookData['pull_request']['number']);
        }

        if (true === \array_key_exists('ref', $webhookData)) {
            $pullRequests = $this->pullRequestRepository->search(
                [
                    PullRequestSearchFilters::HEAD_REF => $webhookData['ref'],
                ]
            );

            if (false === empty($pullRequests)) {
                $pullRequest = array_pop($pullRequests);
            }
        }

        if (null === $pullRequest) {
            return;
        }

        $jiraIssue = $this->getJiraIssueFromPullRequest($pullRequest);

        if (null === $jiraIssue) {
            return;
        }

        $this->addJiraLinkToDescription($pullRequest, $jiraIssue);

        if (\in_array($jiraIssue->fields->status->name, [getenv('JIRA_STATUS_BLOCKED'), getenv('JIRA_STATUS_DONE')], true)) {
            return;
        }

        $this->handleReviewRequiredLabel($pullRequest, $jiraIssue);

        if (false === $this->handleInProgressPullRequest($pullRequest, $jiraIssue)) {
            if (false === $this->isPullRequestApproved($pullRequest)) {
                if ($jiraIssue->fields->status->name !== getenv('JIRA_STATUS_TO_REVIEW')) {
                    $this->jiraIssueRepository->transitionIssueTo($jiraIssue->key, getenv('JIRA_TRANSITION_ID_TO_REVIEW'));
                }
            }
        }
    }
}
