<?php

namespace App\Model;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class JiraIssue
{
    /** @var string */
    private $key;

    /** @var UriInterface */
    private $uri;

    public function __construct(string $key)
    {
        $this->key = $key;
        $this->uri = new Uri(sprintf(
            '%s/browse/%s',
            getenv('JIRA_HOST'),
            $this->key
        ));
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }
}
