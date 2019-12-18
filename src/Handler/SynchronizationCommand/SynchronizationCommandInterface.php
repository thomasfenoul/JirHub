<?php

namespace App\Handler\SynchronizationCommand;

use App\Model\JirHubTask;

interface SynchronizationCommandInterface
{
    public function execute(JirHubTask $jirHubTask): void;
}
