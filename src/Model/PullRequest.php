<?php

namespace App\Model;

class PullRequest
{
    private $number;
    private $title;
    private $body;
    private $headRef;
    private $baseRef;
    private $url;
    private $headSha;
    private $user;
    private $labels;
    private $reviews;

    public function __construct(array $pullRequestData)
    {
        $this->number  = $pullRequestData['number'];
        $this->title   = $pullRequestData['title'];
        $this->body    = $pullRequestData['body'];
        $this->headRef = $pullRequestData['head']['ref'] ?? null;
        $this->baseRef = $pullRequestData['base']['ref'] ?? null;
        $this->url     = $pullRequestData['html_url'];
        $this->headSha = $pullRequestData['head']['sha'] ?? null;
        $this->user    = $pullRequestData['user']['login'];
        $this->labels  = [];
        $this->reviews = [];

        foreach ($pullRequestData['labels'] as $label) {
            $this->labels[] = $label['name'];
        }
    }

    public function getNumber(): int
    {
        return $this->number;
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

    public function getUser(): string
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

    public function getReviews(): array
    {
        return $this->reviews;
    }

    public function setReviews(array $reviews): self
    {
        $this->reviews = $reviews;

        return $this;
    }
}
