<?php

namespace App\Factory;

use App\Model\GitHubUser;

class GitHubUserFactory
{
    public function create(array $userData): GitHubUser
    {
        return new GitHubUser(
            $userData['id'],
            $userData['login'],
            $userData['avatar_url']
        );
    }
}
