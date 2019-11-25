<?php

namespace App\Event;

use App\Model\PullRequest;
use Symfony\Contracts\EventDispatcher\Event;

class PullRequestMergedEvent extends Event
{
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
