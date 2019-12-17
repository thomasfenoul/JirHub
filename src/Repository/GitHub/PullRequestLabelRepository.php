<?php

namespace App\Repository\GitHub;

use App\Client\GitHubClient;
use App\Model\Github\PullRequest;

class PullRequestLabelRepository
{
    /** @var GitHubClient */
    private $client;

    /** @var string */
    private $repositoryOwner;

    /** @var string */
    private $repositoryName;

    public function __construct(
        GitHubClient $client,
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

        $this->client->labels()->add(
            $this->repositoryOwner,
            $this->repositoryName,
            $pullRequest->getId(),
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

        $this->client->labels()->remove(
            $this->repositoryOwner,
            $this->repositoryName,
            $pullRequest->getId(),
            $label
        );

        $pullRequest->removeLabel($label);
    }
}
