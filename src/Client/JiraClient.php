<?php

namespace App\Client;

use App\Exception\UnexpectedContentType;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JiraClient
{
    /** @var HttpClientInterface */
    private $httpClient;

    /** @var string */
    private $host;

    /** @var string */
    private $username;

    /** @var string */
    private $pass;

    public function __construct(
        HttpClientInterface $httpClient,
        string $host,
        string $username,
        string $pass
    ) {
        $this->httpClient = $httpClient;
        $this->host       = $host;
        $this->username   = $username;
        $this->pass       = $pass;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws UnexpectedContentType
     */
    public function get(string $route, array $queryParameters): array
    {
        $response = $this->httpClient->request(
            'GET',
            $this->host . $route,
            [
                'auth_basic' => [$this->username, $this->pass],
                'query'      => $queryParameters,
            ]
        );

        $content = $response->getContent();

        if (0 !== strpos($response->getHeaders()['content-type'][0], 'application/json')) {
            throw new UnexpectedContentType();
        }

        return json_decode($content, true);
    }
}
