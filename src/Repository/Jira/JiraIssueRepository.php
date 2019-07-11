<?php

namespace App\Repository\Jira;

use JiraRestApi\Issue\Issue;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Issue\Transition;
use JiraRestApi\JiraException;
use JsonMapper_Exception;

class JiraIssueRepository
{
    /** @var IssueService */
    private $issueService;

    /**
     * @throws JiraException
     */
    public function __construct()
    {
        $this->issueService = new IssueService();
    }

    /**
     * @throws JiraException
     * @throws JsonMapper_Exception
     */
    public function getIssue(string $issueKey): Issue
    {
        return $this->issueService->get($issueKey);
    }

    /**
     * @throws JiraException
     */
    public function transitionIssueTo(string $issueKey, int $transitionId): void
    {
        $transition = new Transition();
        $transition->setTransitionId($transitionId);
        $transition->setCommentBody('JirHub performed a transition.');

        $this->issueService->transition($issueKey, $transition);
    }
}
