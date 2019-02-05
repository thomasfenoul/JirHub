<?php

namespace App\Dashboard\Query;

interface PullRequestsToDeploy
{
    public function fetch(): array;
}