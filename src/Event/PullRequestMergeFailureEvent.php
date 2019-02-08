<?php


namespace App\Event;


use App\Model\PullRequest;
use Symfony\Component\EventDispatcher\Event;

class PullRequestMergeFailureEvent extends Event
{
    const NAME = 'events.pull_requests.merge.failure';


    /** @var PullRequest */
    protected $pullRequest;

    /** @var string $message */
    protected $message;

    public function __construct(PullRequest $pullRequest, string $message)
    {
        $this->pullRequest = $pullRequest;
        $this->message = $message;
    }

    public function getPullRequest(): PullRequest
    {
        return $this->pullRequest;
    }

    public function setPullRequest(PullRequest $pullRequest): self
    {
        $this->pullRequest = $pullRequest;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }
}