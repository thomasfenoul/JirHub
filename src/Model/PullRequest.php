<?php

namespace App\Model;

class PullRequest
{
    /** @var int */
    private $id;

    /** @var string */
    private $title;

    /** @var string */
    private $body;

    /** @var string */
    private $headRef;

    /** @var string */
    private $baseRef;

    /** @var string */
    private $url;

    /** @var string */
    private $headSha;

    /** @var GitHubUser */
    private $user;

    /** @var string[] */
    private $labels;

    public function __construct(
        int $id,
        string $title,
        string $body,
        string $headRef,
        string $baseRef,
        string $url,
        string $headSha,
        GitHubUser $user,
        array $labels
    ) {
        $this->id      = $id;
        $this->title   = $title;
        $this->body    = $body;
        $this->headRef = $headRef;
        $this->baseRef = $baseRef;
        $this->url     = $url;
        $this->headSha = $headSha;
        $this->user    = $user;
        $this->labels  = $labels;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeadRef(): string
    {
        return $this->headRef;
    }

    public function getBaseRef(): string
    {
        return $this->baseRef;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getHeadSha(): string
    {
        return $this->headSha;
    }

    public function getUser(): GitHubUser
    {
        return $this->user;
    }

    public function getLabels(): array
    {
        return $this->labels;
    }

    public function addLabel(string $label): self
    {
        if (false === \in_array($label, $this->labels, true)) {
            $this->labels[] = $label;
        }

        return $this;
    }

    public function removeLabel(string $label): self
    {
        $index = array_search($label, $this->labels, true);

        if (false !== $index) {
            unset($this->labels[$index]);
        }

        return $this;
    }
}
