<?php

namespace App\Handler;

use App\Dashboard\Handler\DashboardHandler;
use App\Event\LabelsAppliedEvent;
use App\Exception\UnexpectedContentType;
use App\Helper\JiraHelper;
use App\Model\Github\PullRequest;
use App\Model\Github\PullRequestReview;
use App\Model\JiraIssue;
use App\Model\JiraTransition;
use App\Repository\GitHub\Constant\PullRequestSearchFilters;
use App\Repository\GitHub\Constant\PullRequestUpdatableFields;
use App\Repository\GitHub\PullRequestLabelRepository;
use App\Repository\GitHub\PullRequestRepository;
use App\Repository\GitHub\PullRequestReviewRepository;
use App\Repository\Jira\JiraIssueRepository;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GitHubHandler
{
    const CHANGES_REQUESTED = 'CHANGES_REQUESTED';
    const APPROVED          = 'APPROVED';

    const RELEASE_PR_TITLE_PREFIX = 'MEP';

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

    /** @var array */
    private $labels;

    /** @var int */
    private $approveCount;

    /** @var string */
    private $defaultBaseBranch;

    public function __construct(
        PullRequestRepository $pullRequestRepository,
        PullRequestReviewRepository $pullRequestReviewRepository,
        PullRequestLabelRepository $pullRequestLabelRepository,
        JiraIssueRepository $jiraIssueRepository,
        EventDispatcherInterface $eventDispatcher,
        CacheItemPoolInterface $cache,
        array $labels,
        int $approveCount,
        string $defaultBaseBranch
    ) {
        $this->pullRequestRepository       = $pullRequestRepository;
        $this->pullRequestReviewRepository = $pullRequestReviewRepository;
        $this->pullRequestLabelRepository  = $pullRequestLabelRepository;
        $this->jiraIssueRepository         = $jiraIssueRepository;
        $this->eventDispatcher             = $eventDispatcher;
        $this->cache                       = $cache;
        $this->labels                      = $labels;
        $this->approveCount                = $approveCount;
        $this->defaultBaseBranch           = $defaultBaseBranch;
    }

    public function getOpenPullRequestFromHeadBranch(string $headBranchName): ?PullRequest
    {
        $pullRequests = $this->pullRequestRepository->search(
            [PullRequestSearchFilters::HEAD_REF => $headBranchName]
        );

        return (true === empty($pullRequests)) ? null : array_pop($pullRequests);
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
        } catch (\Throwable $t) {
            return null;
        }
    }

    public function isPullRequestApproved(PullRequest $pullRequest): bool
    {
        $approveCount = 0;

        if (null === $pullRequest->getReviews()) {
            $pullRequest->setReviews(array_reverse($this->pullRequestReviewRepository->search($pullRequest)));
        }

        /** @var PullRequestReview $review */
        foreach ($pullRequest->getReviews() as $review) {
            if (self::CHANGES_REQUESTED === $review->getState()) {
                return false;
            }

            if (self::APPROVED === $review->getState()) {
                ++$approveCount;

                if ($approveCount >= $this->approveCount) {
                    return true;
                }
            }
        }

        return false;
    }

    public function doesReviewBranchExists(string $reviewBranchName)
    {
        return \in_array(
            $this->labels['validation_prefix'] . $reviewBranchName,
            $this->labels['validation_environments'],
            true
        );
    }

    public function isReviewBranchAvailable(string $reviewBranchName, PullRequest $pullRequest)
    {
        $pullRequests = $this->pullRequestRepository->search(
            [
                PullRequestSearchFilters::LABELS => [
                    $this->labels['validation_prefix'] . $reviewBranchName,
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
        if ($headBranchName === $this->defaultBaseBranch) {
            return 'OK';
        }

        if (null === $pullRequest) {
            $pullRequest = $this->getOpenPullRequestFromHeadBranch($headBranchName);
        }

        if (null === $pullRequest) {
            return 'Pull Request not found.';
        }

        if ($pullRequest->hasLabel($this->labels['validation_prefix'] . $reviewBranchName)) {
            return 'OK';
        }

        if (empty($pullRequest) || null === $pullRequest) {
            return sprintf(
                'We have not found any pull request with head branch "%s".',
                $headBranchName
            );
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
        $reviewLabels   = $this->labels['validation_environments'];
        $reviewLabels[] = $this->labels['validation_required'];

        foreach ($reviewLabels as $reviewLabel) {
            if ($pullRequest->hasLabel($reviewLabel)) {
                $this->pullRequestLabelRepository->delete(
                    $pullRequest,
                    $reviewLabel
                );
            }
        }
    }

    public function isDeployed(PullRequest $pullRequest): bool
    {
        $reviewLabels = $this->labels['validation_environments'];

        foreach ($reviewLabels as $reviewLabel) {
            if ($pullRequest->hasLabel($reviewLabel)) {
                return true;
            }
        }

        return false;
    }

    public function isValidated(PullRequest $pullRequest): bool
    {
        return $pullRequest->hasLabel($this->labels['validated']);
    }

    /**
     * @throws UnexpectedContentType
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
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
            $this->labels['validation_prefix'] . $reviewBranchName
        );

        $jiraIssueKey = JiraHelper::extractIssueKeyFromString($headBranchName)
            ?? JiraHelper::extractIssueKeyFromString($pullRequest->getTitle());

        if (null !== $jiraIssueKey) {
            $this->jiraIssueRepository->transitionIssueTo(
                $jiraIssueKey,
                new JiraTransition(
                    getenv('JIRA_TRANSITION_ID_TO_VALIDATE'),
                    'JirHub performed a transition'
                )
            );
        }

        $this->eventDispatcher->dispatch(new LabelsAppliedEvent($pullRequest, $reviewBranchName, $jiraIssueKey));

        return true;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws UnexpectedContentType
     */
    public function handleReviewRequiredLabel(PullRequest $pullRequest, ?JiraIssue $jiraIssue = null)
    {
        if ($pullRequest->hasLabel($this->labels['validation_required'])
            && (
                !$this->isPullRequestApproved($pullRequest)
                || $this->isDeployed($pullRequest)
                || $this->isValidated($pullRequest)
            )
        ) {
            $this->pullRequestLabelRepository->delete(
                $pullRequest,
                $this->labels['validation_required']
            );
        }

        if (!$pullRequest->hasLabel($this->labels['validation_required'])
            && $this->isPullRequestApproved($pullRequest)
            && !$this->isDeployed($pullRequest)
            && !$this->isValidated($pullRequest)
        ) {
            $this->pullRequestLabelRepository->create(
                $pullRequest,
                $this->labels['validation_required']
            );

            if (null !== $jiraIssue
                && $jiraIssue->getStatus()->getName() !== getenv('JIRA_STATUS_TO_VALIDATE')
            ) {
                $this->jiraIssueRepository->transitionIssueTo(
                    $jiraIssue->getKey(),
                    new JiraTransition(
                        getenv('JIRA_TRANSITION_ID_TO_VALIDATE'),
                        'JirHub performed a transition'
                    )
                );
            }
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws UnexpectedContentType
     */
    public function handleInProgressPullRequest(PullRequest $pullRequest, JiraIssue $jiraIssue)
    {
        $labels = $this->labels['in_progress'];

        foreach ($labels as $label) {
            if ($pullRequest->hasLabel($label)) {
                if ($jiraIssue->getStatus()->getName() !== getenv('JIRA_STATUS_IN_PROGRESS')) {
                    $this->jiraIssueRepository->transitionIssueTo(
                        $jiraIssue->getKey(),
                        new JiraTransition(
                            getenv('JIRA_TRANSITION_ID_IN_PROGRESS'),
                            'JirHub performed a transition'
                        )
                    );
                }

                return true;
            }
        }

        return false;
    }

    public function addJiraLinkToDescription(PullRequest $pullRequest, ?JiraIssue $jiraIssue)
    {
        $pullRequestBody = $pullRequest->getBody();
        $bodyPrefix      = '> Cette _pull request_ a été ouverte sans ticket Jira associé 👎';

        if (null !== $jiraIssue) {
            $bodyPrefix = JiraHelper::buildIssueUrlFromIssueName($jiraIssue->getKey());
        }

        if (false === strpos($pullRequestBody, $bodyPrefix)) {
            $this->pullRequestRepository->update(
                $pullRequest,
                [PullRequestUpdatableFields::BODY => $bodyPrefix . "\n\n" . $pullRequestBody]
            );
        }
    }

    public function prettifyPullRequestTitle(PullRequest $pullRequest)
    {
        $title = $pullRequest->getTitle();

        $regexPattern  = '/^\[(?<prefix>.*)\]/i';
        $betterPrTitle = null;

        $matches = [];
        preg_match($regexPattern, $title, $matches);

        $labels = [
            'Tech' => 'Tech',
            'bug'  => 'Fix',
        ];

        foreach ($labels as $label => $prefix) {
            if ($pullRequest->hasLabel($label) && empty($matches['prefix'])) {
                $betterPrTitle = sprintf('[%s] %s', $prefix, $title);
            } elseif (!$pullRequest->hasLabel($label) && $matches['prefix'] === $prefix) {
                $betterPrTitle = str_replace(sprintf('[%s] ', $prefix), '', $title);
            }
        }

        if (null !== $betterPrTitle) {
            return $this->pullRequestRepository->update(
                $pullRequest,
                [PullRequestUpdatableFields::TITLE => $betterPrTitle]
            );
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws UnexpectedContentType
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

        if (null === $pullRequest || 0 === strpos($pullRequest->getTitle(), self::RELEASE_PR_TITLE_PREFIX)) {
            return;
        }

        $jiraIssue = $this->getJiraIssueFromPullRequest($pullRequest);
        $this->handleReviewRequiredLabel($pullRequest, $jiraIssue);

        $this->addJiraLinkToDescription($pullRequest, $jiraIssue);

        if (null === $jiraIssue) {
            $this->prettifyPullRequestTitle($pullRequest);

            return;
        }

        if (\in_array(
            $jiraIssue->getStatus()->getName(),
            [getenv('JIRA_STATUS_BLOCKED'), getenv('JIRA_STATUS_DONE')],
            true
        )) {
            return;
        }

        if (false === $this->handleInProgressPullRequest($pullRequest, $jiraIssue)) {
            if (false === $this->isPullRequestApproved($pullRequest)) {
                if ($jiraIssue->getStatus()->getName() !== getenv('JIRA_STATUS_TO_REVIEW')) {
                    try {
                        $this->jiraIssueRepository->transitionIssueTo(
                            $jiraIssue->getKey(),
                            new JiraTransition(
                                getenv('JIRA_TRANSITION_ID_TO_REVIEW'),
                                'JirHub performed a transition'
                            )
                        );
                    } catch (\Throwable $t) {
                    }
                }
            }
        }
    }
}
