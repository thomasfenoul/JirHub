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
    private $username;

    /** @var string */
    private $pass;

    /** @var string */
    private $baseUrl;

    public function __construct(
        HttpClientInterface $httpClient,
        string $host,
        string $username,
        string $pass,
        string $version = '3'
    ) {
        $this->httpClient = $httpClient;
        $this->baseUrl    = $host . '/rest/api/' . $version;
        $this->username   = $username;
        $this->pass       = $pass;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws UnexpectedContentType
     */
    public function request(
        string $route,
        string $method = 'GET',
        array $queryParameters = [],
        string $jsonContent = ''
    ): array {
        $response = $this->httpClient->request(
            $method,
            $this->baseUrl . $route,
            [
                'auth_basic' => [$this->username, $this->pass],
                'query'      => $queryParameters,
                'json'       => $jsonContent,
            ]
        );

        $content = $response->getContent();

        if (0 !== strpos($response->getHeaders()['content-type'][0], 'application/json')) {
            throw new UnexpectedContentType();
        }

        return json_decode($content, true);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws UnexpectedContentType
     */
    public function get(string $route, array $queryParameters = []): array
    {
        return $this->request($route, 'GET', $queryParameters);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws UnexpectedContentType
     */
    public function post(string $route, array $queryParameters = [], string $jsonContent = ''): array
    {
        return $this->request($route, 'POST', $queryParameters, $jsonContent);
    }
}
