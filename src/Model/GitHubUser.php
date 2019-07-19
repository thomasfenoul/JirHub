<?php

namespace App\Model;

class GitHubUser
{
    /** @var int */
    private $id;

    /** @var string */
    private $login;

    /** @var string */
    private $avatarUrl;

    public function __construct(
        int $id,
        string $login,
        string $avatarUrl
    ) {
        $this->id        = $id;
        $this->login     = $login;
        $this->avatarUrl = $avatarUrl;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getAvatarUrl(): string
    {
        return $this->avatarUrl;
    }
}
