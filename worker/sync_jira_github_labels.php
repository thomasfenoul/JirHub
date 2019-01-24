<?php

use App\Handler\GitHubHandler;
use App\Handler\JiraHandler;

require_once 'Worker.php';

try {
    $worker = new Worker();
    /** @var GitHubHandler $gitHubHandler */
    $gitHubHandler = $worker->getService('App\Handler\GitHubHandler');
    /** @var JiraHandler $jiraHandler */
    $jiraHandler = $worker->getService('App\Handler\JiraHandler');
} catch (\Exception $e) {
    throw new \RuntimeException("Erreur dans l'initialisation du worker : " . $e->getMessage());
}

while (true) {
    try {
        $gitHubHandler->synchronize();
    } catch (\Exception $e) {
        $worker->getLogger()->error('Error occured during sync_jira_github_labels process : ' . $e->getMessage());
    }
    sleep($worker->getDelay());
}
