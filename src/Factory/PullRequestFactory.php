<?php

namespace App\Factory;

use App\Model\PullRequest;

class PullRequestFactory
{
    public static function fromArray(array $pullRequestData): PullRequest
    {
        return new PullRequest(
            $pullRequestData['number'],
            $pullRequestData['title'],
            $pullRequestData['body'],
            $pullRequestData['head']['ref'],
            $pullRequestData['base']['ref'],
            $pullRequestData['html_url'],
            $pullRequestData['head']['sha'],
            GitHubUserFactory::fromArray($pullRequestData['user']),
            array_column($pullRequestData['labels'], 'name')
        );
    }
}
