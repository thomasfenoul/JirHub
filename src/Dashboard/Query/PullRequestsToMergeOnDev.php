<?php

namespace App\Dashboard\Query;

interface PullRequestsToMergeOnDev
{
    public function fetch(): array;
}