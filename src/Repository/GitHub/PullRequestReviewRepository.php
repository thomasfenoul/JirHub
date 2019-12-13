<?php

namespace App\Repository\GitHub;

use App\Client\GitHubClient;
use App\Factory\PullRequestReviewFactory;
use App\Model\PullRequest;

class PullRequestReviewRepository
{
    /** @var GitHubClient */
    private $client;

    /** @var string */
    private $repositoryOwner;

    /** @var string */
    private $repositoryName;

    /** @var PullRequestReviewFactory */
    private $pullRequestReviewFactory;

    public function __construct(
        GitHubClient $client,
        string $repositoryOwner,
        string $repositoryName,
        PullRequestReviewFactory $pullRequestReviewFactory
    ) {
        $this->client                   = $client;
        $this->repositoryOwner          = $repositoryOwner;
        $this->repositoryName           = $repositoryName;
        $this->pullRequestReviewFactory = $pullRequestReviewFactory;
    }

    public function search(PullRequest $pullRequest, array $parameters = []): array
    {
        $reviews       = [];
        $apiParameters = [
            'per_page' => 50,
        ];

        if (\array_key_exists('per_page', $parameters)) {
            $apiParameters['per_page'] = $parameters['per_page'];
        }

        $reviewsData = $this->client->reviews()->all(
            $this->repositoryOwner,
            $this->repositoryName,
            $pullRequest->getId(),
            $apiParameters
        );

        foreach ($reviewsData as $reviewData) {
            $reviews[] = $this->pullRequestReviewFactory->create($reviewData);
        }

        return $reviews;
    }
}
