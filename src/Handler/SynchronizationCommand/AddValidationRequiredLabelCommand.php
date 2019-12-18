<?php

namespace App\Handler\SynchronizationCommand;

use App\Handler\GitHubHandler;
use App\Model\JirHubTask;
use App\Repository\GitHub\PullRequestLabelRepository;

final class AddValidationRequiredLabelCommand implements SynchronizationCommandInterface
{
    /** @var GitHubHandler */
    private $githubHandler;

    /** @var string */
    private $label;

    /** @var PullRequestLabelRepository */
    private $pullRequestLabelRepository;

    public function __construct(
        GitHubHandler $githubHandler,
        string $label,
        PullRequestLabelRepository $pullRequestLabelRepository
    ) {
        $this->githubHandler              = $githubHandler;
        $this->label                      = $label;
        $this->pullRequestLabelRepository = $pullRequestLabelRepository;
    }

    public function execute(JirHubTask $jirHubTask): void
    {
        $pullRequest = $jirHubTask->getGithubPullRequest();

        if (
            false === $pullRequest->hasLabel($this->label)
            && $this->githubHandler->isPullRequestApproved($pullRequest)
            && false === $this->githubHandler->isDeployed($pullRequest)
            && false === $this->githubHandler->isValidated($pullRequest)
        ) {
            $this->pullRequestLabelRepository->create(
                $pullRequest,
                $this->label
            );
        }
    }
}
