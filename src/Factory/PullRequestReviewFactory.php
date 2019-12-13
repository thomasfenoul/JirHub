<?php

namespace App\Factory;

use App\Model\PullRequestReview;

class PullRequestReviewFactory
{
    /** @var GitHubUserFactory */
    private $githubUserFactory;

    public function __construct(GitHubUserFactory $gitHubUserFactory)
    {
        $this->githubUserFactory = $gitHubUserFactory;
    }

    public function create(array $pullRequestReviewData): PullRequestReview
    {
        return new PullRequestReview(
            $pullRequestReviewData['id'],
            $this->githubUserFactory->create($pullRequestReviewData['user']),
            $pullRequestReviewData['body'],
            $pullRequestReviewData['state'],
            $pullRequestReviewData['html_url']
        );
    }
}
