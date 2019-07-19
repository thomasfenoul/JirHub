<?php

namespace App\Repository\GitHub;

use App\Model\PullRequest;
use Github\Client;

class PullRequestLabelRepository
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

    public function create(PullRequest $pullRequest, string $label): void
    {
        $labels = $pullRequest->getLabels();

        if (\in_array($label, $labels, true)) {
            return;
        }

        $this->client->issue()->labels()->add(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            $pullRequest->getNumber(),
            $label
        );

        $pullRequest->addLabel($label);
    }

    public function delete(PullRequest $pullRequest, string $label): void
    {
        $labels = $pullRequest->getLabels();

        if (!\in_array($label, $labels, true)) {
            return;
        }

        $this->client->issue()->labels()->remove(
            getenv('GITHUB_REPOSITORY_OWNER'),
            getenv('GITHUB_REPOSITORY_NAME'),
            $pullRequest->getNumber(),
            $label
        );

        $pullRequest->removeLabel($label);
    }
}
