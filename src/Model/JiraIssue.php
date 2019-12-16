<?php

namespace App\Model;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class JiraIssue
{
    /** @var string */
    private $key;

    /** @var JiraIssueStatus */
    private $status;

    /** @var JiraIssueType */
    private $type;

    /** @var UriInterface */
    private $uri;

    public function __construct(
        string $key,
        JiraIssueStatus $status,
        JiraIssueType $type,
        Uri $uri
    ) {
        $this->key    = $key;
        $this->status = $status;
        $this->type   = $type;
        $this->uri    = $uri;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getStatus(): JiraIssueStatus
    {
        return $this->status;
    }

    public function getIssueType(): JiraIssueType
    {
        return $this->getIssueType();
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }
}
