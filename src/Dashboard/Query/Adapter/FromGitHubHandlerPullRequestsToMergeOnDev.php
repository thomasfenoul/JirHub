<?php


namespace App\Dashboard\Query\Adapter;


use App\Dashboard\Query\PullRequestsToMergeOnDev;
use App\Handler\GitHubHandler;

class FromGitHubHandlerPullRequestsToMergeOnDev implements PullRequestsToMergeOnDev
{
    /** @var GitHubHandler */
    protected $handler;

    public function __construct(GitHubHandler $handler)
    {
        $this->handler = $handler;
    }

    public function fetch(): array
    {
        return $this->handler->getOpenPullRequestsWithLabel("~validated");
    }
}