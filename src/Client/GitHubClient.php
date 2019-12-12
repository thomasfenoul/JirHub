<?php

namespace App\Client;

use Github\Api\Issue;
use Github\Api\Issue\Labels;
use Github\Api\PullRequest;
use Github\Api\PullRequest\Review;
use Github\Api\Repository\Commits;
use Github\Client;
use Github\HttpClient\Builder as HttpClientBuilder;
use Lcobucci\JWT\Builder as JWTBuilder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;

class GitHubClient
{
    /** @var Client */
    private $client;

    public function __construct(
        HttpClientBuilder $httpClientBuilder,
        JWTBuilder $jwtBuilder,
        string $gitHubAppId,
        string $gitHubPrivateRsaKey,
        string $gitHubAppInstallationId
    ) {
        $this->client = new Client($httpClientBuilder, 'machine-man-preview');

        $jwt = $jwtBuilder
            ->issuedBy($gitHubAppId)
            ->issuedAt(time())
            ->expiresAt(time() + 60)
            ->getToken(new Sha256(), new Key($gitHubPrivateRsaKey))
        ;

        $this->client->authenticate($jwt, null, Client::AUTH_JWT);
        $res = $this->client->apps()->createInstallationToken($gitHubAppInstallationId);

        $this->client->authenticate($res['token'], null, Client::AUTH_HTTP_TOKEN);
    }

    public function issues(): Issue
    {
        return $this->client->issues();
    }

    public function pullRequests(): PullRequest
    {
        return $this->client->pullRequests();
    }

    public function labels(): Labels
    {
        return $this->client->issues()->labels();
    }

    public function reviews(): Review
    {
        return $this->client->pullRequests()->reviews();
    }

    public function commits(): Commits
    {
        return $this->client->repositories()->commits();
    }
}
