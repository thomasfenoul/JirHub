<?php

namespace App\Repository\GitHub;

use App\Client\GitHubClient;

class CommitRepository
{
    /** @var GitHubClient */
    private $client;

    /** @var string */
    private $repositoryOwner;

    /** @var string */
    private $repositoryName;

    public function __construct(GitHubClient $client, string $repositoryOwner, string $repositoryName)
    {
        $this->client          = $client;
        $this->repositoryOwner = $repositoryOwner;
        $this->repositoryName  = $repositoryName;
    }

    public function getChangelog(string $base, string $head): array
    {
        return $this->client->commits()->compare(
            $this->repositoryOwner,
            $this->repositoryName,
            $base,
            $head
        );
    }
}
