<?php

namespace App\Model;

class ReviewEnvironment
{
    /** @var string $name */
    protected $name;

    /** @var string $jiraIssueKey */
    protected $jiraIssueKey;

    /** @var PullRequest $pullRequest */
    protected $pullRequest;

    /** @var string */
    private $pullRequestTitle;

    public function __construct(string $name, ?string $jiraIssueKey = null, ?PullRequest $pullRequest = null)
    {
        $this->name         = $name;
        $this->jiraIssueKey = $jiraIssueKey;
        $this->pullRequest  = $pullRequest;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPullRequest(): ?PullRequest
    {
        return $this->pullRequest;
    }

    public function setPullRequest(?PullRequest $pullRequest): self
    {
        $this->pullRequest = $pullRequest;

        return $this;
    }

    public function getJiraIssueKey(): ?string
    {
        return $this->jiraIssueKey;
    }

    public function setJiraIssueKey(?string $jiraIssueKey = null): self
    {
        $this->jiraIssueKey = $jiraIssueKey;

        return $this;
    }

    public function getPullRequestTitle(): string
    {
        if (null === $this->pullRequestTitle) {
            $this->pullRequestTitle = ucfirst(
                trim(str_ireplace($this->getJiraIssueKey(), '', $this->pullRequest->getTitle()), ' :')
            );
        }

        return $this->pullRequestTitle;
    }
}
