<?php

namespace App\Dashboard\Query\Adapter;

use App\Dashboard\Query\ReviewEnvironments;
use App\Handler\GitHubHandler;
use App\Model\ReviewEnvironment;

class FromGitHubHandlerReviewEnvironments implements ReviewEnvironments
{
    /** @var GitHubHandler */
    protected $handler;

    public function __construct(GitHubHandler $handler)
    {
        $this->handler = $handler;
    }

    public function fetch(): array
    {
        $environments = [
            new ReviewEnvironment('red'),
            new ReviewEnvironment('blue'),
            new ReviewEnvironment('green'),
            new ReviewEnvironment('yellow'),
        ];

        foreach ($environments as $environment) {
            $pullRequestsOnEnvironment = $this->handler->getOpenPullRequestsWithLabel('~validation-' . $environment->getName());

            if (!empty($pullRequestsOnEnvironment)) {
                $environment->setPullRequest($pullRequestsOnEnvironment[0]);
            }
        }

        return $environments;
    }
}
