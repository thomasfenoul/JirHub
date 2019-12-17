<?php

namespace App\Factory;

use App\Model\Github\PullRequest;

class PullRequestFactory
{
    /** @var GithubUserFactory */
    private $githubUserFactory;

    public function __construct(GithubUserFactory $githubUserFactory)
    {
        $this->githubUserFactory = $githubUserFactory;
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
