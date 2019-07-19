<?php

namespace App\Factory;

use App\Model\PullRequestReview;

class PullRequestReviewFactory
{
    public static function fromArray(array $pullRequestReviewData): PullRequestReview
    {
        return new PullRequestReview(
            $pullRequestReviewData['id'],
            GitHubUserFactory::fromArray($pullRequestReviewData['user']),
            $pullRequestReviewData['body'],
            $pullRequestReviewData['state'],
            $pullRequestReviewData['html_url']
        );
    }
}
