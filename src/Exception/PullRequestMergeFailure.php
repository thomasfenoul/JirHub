<?php


namespace App\Exception;


use App\Model\PullRequest;

class PullRequestMergeFailure extends JirHubException
{
    /** @var PullRequest */
    protected $pullRequest;

    public function getPullRequest(): PullRequest
    {
        return $this->pullRequest;
    }

    public function setPullRequest(PullRequest $pullRequest): PullRequestMergeFailure
    {
        $this->pullRequest = $pullRequest;
        return $this;
    }
}