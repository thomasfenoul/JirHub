<?php

namespace App\Dashboard\Query\Adapter;

use App\Dashboard\Query\ReviewEnvironments;
use App\Handler\GitHubHandler;
use App\Helper\JiraHelper;
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

        /** @var ReviewEnvironment $environment */
        foreach ($environments as $environment) {
            $pullRequestsOnEnvironment = $this->handler->getOpenPullRequestsWithLabel('~validation-' . $environment->getName());

            if (!empty($pullRequestsOnEnvironment)) {
                $pullRequest = $this->handler->getPullRequest($pullRequestsOnEnvironment[0]->getNumber());

                $environment
                    ->setPullRequest($pullRequest)
                    ->setJiraIssueKey(
                        JiraHelper::extractIssueKeyFromString($pullRequest->getHeadRef())
                        ?? JiraHelper::extractIssueKeyFromString($pullRequest->getTitle())
                    )
                ;
            }
        }

        return $environments;
    }
}
