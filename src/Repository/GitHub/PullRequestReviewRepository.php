<?php

namespace App\Repository\GitHub;

use App\Factory\PullRequestReviewFactory;
use App\Model\PullRequest;
use Github\Client;

class PullRequestReviewRepository
{
    /** @var Client */
    private $client;

    /** @var string */
    private $repositoryOwner;

    /** @var string */
    private $repositoryName;

    public function __construct(
        Client $client,
        string $repositoryOwner,
        string $repositoryName
    ) {
        $this->client          = $client;
        $this->repositoryOwner = $repositoryOwner;
        $this->repositoryName  = $repositoryName;
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

        $reviewsData = $this->client->pullRequests()->reviews()->all(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            $pullRequest->getId(),
            $apiParameters
        );

        foreach ($reviewsData as $reviewData) {
            $reviews[] = PullRequestReviewFactory::fromArray($reviewData);
        }

        return $reviews;
    }
}
