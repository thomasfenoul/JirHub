<?php

namespace App\External;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HerokuApi
{
    /** @var HttpClientInterface */
    private $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        string              $domain,
        string              $apiKey
    )
    {
        $this->httpClient = $httpClient->withOptions([
            'base_uri' => $domain,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/vnd.heroku+json; version=3',
            ]
        ]);
    }

    public function updateFormationQuantity(string $appName, string $formationType, int $quantity): array
    {
        $res = $this->httpClient->request(
            Request::METHOD_PATCH,
            'apps/' . $appName . '/formation/' . $formationType,
            [
                'json' => [
                    'quantity' => $quantity,
                ],
            ]
        );

        return $res->toArray();
    }
}
