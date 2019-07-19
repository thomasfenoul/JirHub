<?php

namespace App\Factory;

use App\Model\GitHubUser;

class GitHubUserFactory
{
    public static function fromArray(array $userData): GitHubUser
    {
        return new GitHubUser(
            $userData['id'],
            $userData['login'],
            $userData['avatar_url']
        );
    }
}
