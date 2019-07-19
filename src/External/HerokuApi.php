<?php

namespace App\External;

use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;

class HerokuApi
{
    /** @var Client $guzzleClient */
    private $guzzleClient;

    public function __construct(string $domain, string $apiKey)
    {
        if (null === $this->guzzleClient) {
            $this->guzzleClient = new Client(
                [
                    'base_uri' => $domain,
                    'headers'  => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/vnd.heroku+json; version=3',
                    ],
                ]
            );
        }
    }

    /**
     * @throws GuzzleException
     */
    public function updateFormationQuantity(string $appName, string $formationType, int $quantity): array
    {
        $res = $this->guzzleClient->request(
            Request::METHOD_PATCH,
            'apps/' . $appName . '/formation/' . $formationType,
            [
                'json' => [
                    'quantity' => $quantity,
                ],
            ]
        );

        return json_decode($res->getBody()->getContents(), true);
    }
}
