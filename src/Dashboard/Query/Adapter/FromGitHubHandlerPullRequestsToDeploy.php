<?php


namespace App\Dashboard\Query\Adapter;


use App\Dashboard\Query\PullRequestsToDeploy;
use App\Handler\GitHubHandler;

class FromGitHubHandlerPullRequestsToDeploy implements PullRequestsToDeploy
{
    /** @var GitHubHandler */
    protected $handler;

    public function __construct(GitHubHandler $handler)
    {
        $this->handler = $handler;
    }

    public function fetch(): array
    {
        return $this->handler->getOpenPullRequestsWithLabel("~validation-required");
    }
}