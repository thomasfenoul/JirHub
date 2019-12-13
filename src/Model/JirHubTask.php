<?php

namespace App\Model;

class JirHubTask
{
    /** @var JiraIssue */
    private $jiraIssue;

    /** @var PullRequest */
    private $githubPullRequest;

    public function __construct(JiraIssue $jiraIssue, PullRequest $githubPullRequest)
    {
        $this->jiraIssue         = $jiraIssue;
        $this->githubPullRequest = $githubPullRequest;
    }
}
