<?php


namespace App\Event;


use App\Model\PullRequest;
use Symfony\Component\EventDispatcher\Event;

class PullRequestMergedEvent extends Event
{
    const NAME = 'events.pull_requests.merged';

    /** @var PullRequest $pullRequest */
    protected $pullRequest;

    public function __construct(PullRequest $pullRequest)
    {
        $this->pullRequest = $pullRequest;
    }

    public function getPullRequest(): PullRequest
    {
        return $this->pullRequest;
    }
}