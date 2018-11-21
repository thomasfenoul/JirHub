<?php

namespace App\Handler;

use JiraRestApi\Issue\Issue;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Issue\Transition;
use JiraRestApi\JiraException;
use JiraRestApi\Project\Project;
use JiraRestApi\Project\ProjectService;
use JsonMapper_Exception;

class JiraHandler
{
    /** @var ProjectService */
    private $projectService;

    /** @var IssueService */
    private $issueService;

    /**
     * @throws JiraException
     */
    public function __construct()
    {
        $this->projectService = new ProjectService();
        $this->issueService   = new IssueService();
    }

    /**
     * @throws JiraException
     * @throws JsonMapper_Exception
     */
    public function getProjectInfo(string $projectName): Project
    {
        return $this->projectService->get($projectName);
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
    public function transitionIssueTo(string $issueKey, string $transitionName)
    {
        $transition = new Transition();
        $transition->setTransitionName($transitionName);
        $transition->setCommentBody('JirHub performed a transition.');

        $this->issueService->transition($issueKey, $transition);
    }

    public static function extractIssueNameFromString(string $str)
    {
        $matches = [];
        preg_match(getenv('JIRA_ISSUE_REGEX_PATTERN'), $str, $matches);

        if (1 === \count($matches)) {
            return $matches[0];
        }

        return null;
    }

    public static function buildIssueUrlFromIssueName(string $issueName)
    {
        return getenv('JIRA_HOST') . '/browse/' . $issueName;
    }
}
