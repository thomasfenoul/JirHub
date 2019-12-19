<?php

namespace App\Handler\SynchronizationCommand;

use App\Handler\GitHubHandler;
use App\Model\JirHubTask;
use App\Repository\GitHub\PullRequestLabelRepository;
use Psr\Log\LoggerInterface;

final class AddValidationRequiredLabelCommand implements SynchronizationCommandInterface
{
    /** @var GitHubHandler */
    private $githubHandler;

    /** @var string */
    private $label;

    /** @var PullRequestLabelRepository */
    private $pullRequestLabelRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        GitHubHandler $githubHandler,
        string $label,
        PullRequestLabelRepository $pullRequestLabelRepository,
        LoggerInterface $logger
    ) {
        $this->githubHandler              = $githubHandler;
        $this->label                      = $label;
        $this->pullRequestLabelRepository = $pullRequestLabelRepository;
        $this->logger                     = $logger;
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

            $this->logger->info(
                sprintf(
                    'Added label %s to pull request #%d',
                    $this->label,
                    $pullRequest->getId()
                )
            );
        }
    }
}
