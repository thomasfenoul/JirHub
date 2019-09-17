<?php

namespace App\Model;

class ReviewEnvironment
{
    /** @var string $name */
    protected $name;

    /** @var PullRequest $pullRequest */
    protected $pullRequest;

    /** @var string */
    private $pullRequestTitle;

    public function __construct(string $name, ?PullRequest $pullRequest = null)
    {
        $this->name        = $name;
        $this->pullRequest = $pullRequest;
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

    public function getPullRequestTitle(): string
    {
        if (null === $this->pullRequestTitle) {
            $issueKey = '';

            if (null !== $this->pullRequest->getJiraIssue()) {
                $issueKey = $this->pullRequest->getJiraIssue()->getKey();
            }

            $this->pullRequestTitle = ucfirst(
                trim(str_ireplace($issueKey, '', $this->pullRequest->getTitle()), ' :|')
            );
        }

        return $this->pullRequestTitle;
    }
}
