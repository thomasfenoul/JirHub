<?php

namespace App\Factory;

use App\Model\Github\PullRequestReview;

class PullRequestReviewFactory
{
    /** @var GithubUserFactory */
    private $githubUserFactory;

    public function __construct(GithubUserFactory $githubUserFactory)
    {
        $this->githubUserFactory = $githubUserFactory;
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
