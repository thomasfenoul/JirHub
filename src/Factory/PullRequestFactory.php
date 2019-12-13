<?php

namespace App\Factory;

use App\Model\PullRequest;

class PullRequestFactory
{
    /** @var GitHubUserFactory */
    private $githubUserFactory;

    public function __construct(GitHubUserFactory $gitHubUserFactory)
    {
        $this->githubUserFactory = $gitHubUserFactory;
    }

    public function create(array $pullRequestData): PullRequest
    {
        return new PullRequest(
            $pullRequestData['number'],
            $pullRequestData['title'],
            $pullRequestData['body'],
            $pullRequestData['head']['ref'],
            $pullRequestData['base']['ref'],
            $pullRequestData['html_url'],
            $pullRequestData['head']['sha'],
            $this->githubUserFactory->create($pullRequestData['user']),
            array_column($pullRequestData['labels'], 'name')
        );
    }
}
