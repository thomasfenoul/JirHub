<?php

namespace App\Repository\GitHub;

use Github\Client;

class CommitRepository
{
    /** @var Client */
    private $client;

    /** @var string */
    private $repositoryOwner;

    /** @var string */
    private $repositoryName;

    public function __construct(Client $client, string $repositoryOwner, string $repositoryName)
    {
        $this->client = $client;
        $this->repositoryOwner = $repositoryOwner;
        $this->repositoryName = $repositoryName;
    }

    public function getChangelog(string $base, string $head): array
    {
        return $this->client->repo()->commits()->compare(
            $this->repositoryOwner,
            $this->repositoryName,
            $base,
            $head
        );
    }
}
