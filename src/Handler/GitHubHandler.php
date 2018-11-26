<?php

namespace App\Handler;

use Github\Client as GitHubClient;

class GitHubHandler
{
    /** @var GitHubClient */
    private $gitHubClient;

    /** @var SlackHandler */
    private $slackHandler;

    /** @var JiraHandler */
    private $jiraHandler;

    public function __construct(SlackHandler $slackHandler, JiraHandler $jiraHandler)
    {
        $this->gitHubClient = new GitHubClient();
        $this->gitHubClient->authenticate(getenv('GITHUB_TOKEN'), null, GitHubClient::AUTH_HTTP_TOKEN);
        $this->slackHandler = $slackHandler;
        $this->jiraHandler  = $jiraHandler;
    }

    public function getOpenPullRequests(array $filters = []): array
    {
        return $this->gitHubClient->api('pull_request')->all(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            ['state' => 'open'] + $filters
        );
    }

    public function getOpenPullRequestsWithLabel(string $label)
    {
        return $this->gitHubClient->api('issue')->all(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            [
                'state'  => 'open',
                'labels' => $label,
            ]
        );
    }

    public function getOpenPullRequestFromHeadBranch(string $headBranchName)
    {
        $openPullRequests = $this->getOpenPullRequests();

        foreach ($openPullRequests as $openPullRequest) {
            $isHeadMatching = strtoupper($openPullRequest['head']['ref']) === strtoupper($headBranchName);
            $isBaseDefault  = strtoupper($openPullRequest['base']['ref']) === strtoupper(getenv('GITHUB_DEFAULT_BASE_BRANCH'));

            if ($isHeadMatching && $isBaseDefault) {
                return $openPullRequest;
            }
        }

        return null;
    }

    public function getOpenPullRequestFromJiraIssueKey(string $jiraIssueName)
    {
        $openPullRequests = $this->getOpenPullRequests();

        foreach ($openPullRequests as $openPullRequest) {
            if (false !== strpos(strtoupper($openPullRequest['head']['ref']), strtoupper($jiraIssueName))
                || false !== strpos(strtoupper($openPullRequest['title']), strtoupper($jiraIssueName))) {
                return $openPullRequest;
            }
        }

        return null;
    }

    public function getPullRequest(int $pullRequestNumber)
    {
        return $this->gitHubClient->api('pull_request')->show(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            $pullRequestNumber
        );
    }

    public function addLabelToPullRequest(string $label, int $pullRequestNumber)
    {
        $this->gitHubClient->api('issue')->labels()->add(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            $pullRequestNumber,
            $label
        );
    }

    public function removeLabelFromPullRequest(string $label, int $pullRequestNumber)
    {
        $this->gitHubClient->api('issue')->labels()->remove(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            $pullRequestNumber,
            $label
        );
    }

    public function getPullRequestReviews(int $pullRequestNumber)
    {
        return $this->gitHubClient->api('pull_request')->reviews()->all(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            $pullRequestNumber
        );
    }

    public function mergePullRequest(string $headBranchName)
    {
        $pullRequest = $this->getOpenPullRequestFromHeadBranch($headBranchName);

        try {
            $this->gitHubClient->api('pull_request')->merge(
                getenv('GITHUB_REPOSITORY_OWNER'),
                getenv('GITHUB_REPOSITORY_NAME'),
                $pullRequest['number'],
                $pullRequest['title'],
                $pullRequest['head']['sha']
            );
        } catch (\Exception $e) {
            return 'JirHub could not merge this pull request : ' . $pullRequest['html_url'] . "\nError : " . $e->getMessage();
        }

        return true;
    }

    public function isBranchIgnored(string $branchName): bool
    {
        $branchName         = strtoupper($branchName);
        $explodedBranchName = explode('/', $branchName);
        $ignoredBranches    = explode(',', getenv('GITHUB_IGNORED_PREFIXES'));

        return \in_array($explodedBranchName[0], $ignoredBranches);
    }

    public function isPullRequestApproved(int $pullRequestNumber): bool
    {
        $reviews      = array_reverse($this->getPullRequestReviews($pullRequestNumber));
        $approveCount = 0;

        foreach ($reviews as $review) {
            if ('CHANGES_REQUESTED' === $review['state']) {
                return false;
            }

            if ('APPROVED' === $review['state']) {
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

    public function isReviewBranchAvailable(string $reviewBranchName, ?int $pullRequestNumber = null)
    {
        $pullRequests = $this->getOpenPullRequestsWithLabel(getenv('GITHUB_REVIEW_ENVIRONMENT_PREFIX') . $reviewBranchName);

        return 0 === \count($pullRequests)
            || (1 === \count($pullRequests) && $pullRequests[0]['number'] === $pullRequestNumber);
    }

    public function checkDeployability(string $headBranchName, string $reviewBranchName, array $pullRequest = [])
    {
        if (empty($pullRequest)) {
            $pullRequest = $this->getOpenPullRequestFromHeadBranch($headBranchName);
        }

        if (empty($pullRequest) || null === $pullRequest) {
            echo 'We have not found any pull request with head branch "' . $headBranchName . '".';

            die;
        }

        if ($this->isBranchIgnored($headBranchName)) {
            echo 'The branch "' . $headBranchName . '" does not need to be reviewed.';

            die;
        }

        if (!$this->doesReviewBranchExists($reviewBranchName)) {
            echo 'The review branch "' . $reviewBranchName . '" does not exist or does not have any attributed label.';

            die;
        }

        if (!$this->isReviewBranchAvailable($reviewBranchName, $pullRequest['number'])) {
            echo 'The review branch "' . $reviewBranchName . '" is already used by another PR.';

            die;
        }

        if (!$this->isPullRequestApproved($pullRequest['number'])) {
            echo 'The pull request with head branch "' . $headBranchName . '" does not have enough approving reviews or has requested changes.';

            die;
        }

        return 'OK';
    }

    public function removeReviewLabels(array $pullRequest)
    {
        $reviewLabels   = explode(',', getenv('GITHUB_REVIEW_LABELS'));
        $reviewLabels[] = getenv('GITHUB_REVIEW_REQUIRED_LABEL');

        foreach ($reviewLabels as $reviewLabel) {
            if ($this->hasLabel($pullRequest, $reviewLabel)) {
                $this->removeLabelFromPullRequest($reviewLabel, $pullRequest['number']);
            }
        }
    }

    public function hasLabel(array $pullRequest, string $search)
    {
        foreach ($pullRequest['labels'] as $pullRequestLabel) {
            if ($pullRequestLabel['name'] === $search) {
                return true;
            }
        }

        return false;
    }

    public function isDeployed(array $pullRequest): bool
    {
        $reviewLabels = explode(',', getenv('GITHUB_REVIEW_LABELS'));

        foreach ($reviewLabels as $reviewLabel) {
            if ($this->hasLabel($pullRequest, $reviewLabel)) {
                return true;
            }
        }

        return false;
    }

    public function applyLabels(string $headBranchName, string $reviewBranchName): bool
    {
        $pullRequest = $this->getOpenPullRequestFromHeadBranch($headBranchName);

        if (!$this->checkDeployability($headBranchName, $reviewBranchName, $pullRequest)) {
            return false;
        }

        $this->removeReviewLabels($pullRequest);
        $this->addLabelToPullRequest(getenv('GITHUB_REVIEW_ENVIRONMENT_PREFIX') . $reviewBranchName, $pullRequest['number']);

        $jiraIssueKey = JiraHandler::extractIssueKeyFromString($headBranchName)
            ?? JiraHandler::extractIssueKeyFromString($pullRequest['title']);

        $subject = $headBranchName;
        $blame   = '(demander à ' . $pullRequest['user']['login'] . ' de retrouver la tâche Jira)';

        if (null !== $jiraIssueKey) {
            $subject = JiraHandler::buildIssueUrlFromIssueName($jiraIssueKey);
            $this->jiraHandler->transitionIssueTo($jiraIssueKey, getenv('JIRA_STATUS_TO_VALIDATE'));
            $blame = '';
        }

        $this->slackHandler->sendMessage(
            getenv('SLACK_LINK_TAG') . ' ' . $subject . ' dispo sur  `' . $reviewBranchName . '` ' . $blame . "\n Pull request : " . $pullRequest['html_url'],
            getenv('SLACK_REVIEW_CHANNEL')
        );

        return true;
    }

    public function addValidationRequiredLabels(): array
    {
        $openPullRequests = $this->getOpenPullRequests();
        $res              = [
            'removed' => [],
            'added'   => [],
        ];

        foreach ($openPullRequests as $openPullRequest) {
            if (
                $this->hasLabel($openPullRequest, getenv('GITHUB_REVIEW_REQUIRED_LABEL'))
                && (
                    $this->isBranchIgnored($openPullRequest['head']['ref'])
                    || !$this->isPullRequestApproved($openPullRequest['number'])
                    || $this->isDeployed($openPullRequest)
                )
            ) {
                $this->removeLabelFromPullRequest(getenv('GITHUB_REVIEW_REQUIRED_LABEL'), $openPullRequest['number']);
                $res['removed'] += $openPullRequest;
            }

            if (
                !$this->hasLabel($openPullRequest, getenv('GITHUB_REVIEW_REQUIRED_LABEL'))
                && !$this->isBranchIgnored($openPullRequest['head']['ref'])
                && $this->isPullRequestApproved($openPullRequest['number'])
                && !$this->isDeployed($openPullRequest)
            ) {
                $this->addLabelToPullRequest(getenv('GITHUB_REVIEW_REQUIRED_LABEL'), $openPullRequest['number']);
                $res['added'] += $openPullRequest;
            }
        }

        return $res;
    }
}
