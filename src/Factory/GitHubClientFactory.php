<?php

namespace App\Factory;

use Github\Client;
use Github\HttpClient\Builder as HttpClientBuilder;
use Lcobucci\JWT\Builder as JWTBuilder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;

class GitHubClientFactory
{
    public static function createGitHubClient(
        HttpClientBuilder $httpClientBuilder,
        JWTBuilder $jwtBuilder,
        string $gitHubAppId,
        string $gitHubPrivateRsaKey,
        string $gitHubAppInstallationId
    ): Client {
        $client = new Client($httpClientBuilder, 'machine-man-preview');
        $jwt    = $jwtBuilder
            ->issuedBy($gitHubAppId)
            ->issuedAt(time())
            ->expiresAt(time() + 60)
            ->getToken(new Sha256(), new Key($gitHubPrivateRsaKey))
        ;

        $client->authenticate($jwt, null, Client::AUTH_JWT);
        $res = $client->apps()->createInstallationToken($gitHubAppInstallationId);

        $client->authenticate($res['token'], null, Client::AUTH_HTTP_TOKEN);

        return $client;
    }
}
