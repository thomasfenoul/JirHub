<?php


namespace App\Model;


class ReviewEnvironment
{
    /** @var string $name */
    protected $name;

    /** @var PullRequest $pullRequest */
    protected $pullRequest;

    public function __construct(string $name, ?PullRequest $pullRequest = null)
    {
        $this->name = $name;
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
}