<?php

namespace App\Factory;

use App\Model\Github\GithubUser;

class GithubUserFactory
{
    public function create(array $userData): GithubUser
    {
        return new GithubUser(
            $userData['id'],
            $userData['login'],
            $userData['avatar_url']
        );
    }
}
