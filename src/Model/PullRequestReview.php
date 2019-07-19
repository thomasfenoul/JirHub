<?php

namespace App\Model;

class PullRequestReview
{
    /** @var int */
    private $id;

    /** @var GitHubUser */
    private $user;

    /** @var string */
    private $body;

    /** @var string */
    private $state;

    /** @var string */
    private $url;

    public function __construct(
        int $id,
        GitHubUser $user,
        string $body,
        string $state,
        string $url
    ) {
        $this->id    = $id;
        $this->user  = $user;
        $this->body  = $body;
        $this->state = $state;
        $this->url   = $url;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): GitHubUser
    {
        return $this->user;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
