<?php

namespace App\Handler;

use App\Model\JirHubTask;

class SynchronizationHandler
{
    /** @var GitHubHandler */
    private $githubHandler;

    public function __construct(GitHubHandler $githubHandler)
    {
        $this->githubHandler = $githubHandler;
    }

    public function synchronize(JirHubTask $jirHubTask): void
    {
    }
}
