<?php

namespace App\Model;

use App\Model\Github\PullRequest;

class JirHubTask
{
    /** @var PullRequest */
    private $githubPullRequest;

    /** @var JiraIssue|null */
    private $jiraIssue;

    public function __construct(PullRequest $githubPullRequest, ?JiraIssue $jiraIssue = null)
    {
        $this->githubPullRequest = $githubPullRequest;
        $this->jiraIssue         = $jiraIssue;
    }

    public function getGithubPullRequest(): PullRequest
    {
        return $this->githubPullRequest;
    }

    public function getJiraIssue(): ?JiraIssue
    {
        return $this->jiraIssue;
    }
}
