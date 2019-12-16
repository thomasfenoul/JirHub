<?php

namespace App\Repository;

use App\Helper\JiraHelper;
use App\Model\JirHubTask;
use App\Model\PullRequest;
use App\Repository\Jira\JiraIssueRepository;

class JirHubTaskRepository
{
    private $jiraIssueRepository;

    public function __construct(JiraIssueRepository $jiraIssueRepository)
    {
        $this->jiraIssueRepository = $jiraIssueRepository;
    }

    public function getJirHubTaskFromPullRequest(PullRequest $pullRequest): JirHubTask
    {
        $jiraIssue = null;
        $key       = JiraHelper::extractIssueKeyFromString($pullRequest->getHeadRef())
            ?? JiraHelper::extractIssueKeyFromString($pullRequest->getTitle());

        if (null !== $key) {
            try {
                $jiraIssue = $this->jiraIssueRepository->getIssue($key);
            } catch (\Throwable $t) {
            }
        }

        return new JirHubTask($pullRequest, $jiraIssue);
    }
}
