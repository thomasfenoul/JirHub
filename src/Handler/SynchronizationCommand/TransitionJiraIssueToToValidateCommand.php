<?php

namespace App\Handler\SynchronizationCommand;

use App\Exception\UnexpectedContentType;
use App\Model\JiraTransition;
use App\Model\JirHubTask;
use App\Repository\Jira\JiraIssueRepository;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class TransitionJiraIssueToToValidateCommand implements SynchronizationCommandInterface
{
    /** @var JiraIssueRepository */
    private $jiraIssueRepository;

    /** @var string */
    private $label;

    /** @var string */
    private $statusId;

    /** @var int */
    private $transitionId;

    public function __construct(
        JiraIssueRepository $jiraIssueRepository,
        string $label,
        int $statusId,
        int $transitionId
    ) {
        $this->jiraIssueRepository = $jiraIssueRepository;
        $this->label               = $label;
        $this->statusId            = $statusId;
        $this->transitionId        = $transitionId;
    }

    /**
     * @throws UnexpectedContentType
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function execute(
        JirHubTask $jirHubTask
    ): void {
        if (null === $jiraIssue = $jirHubTask->getJiraIssue()) {
            return;
        }

        $pullRequest = $jirHubTask->getGithubPullRequest();

        if (
            true === $pullRequest->hasLabel($this->label)
            && $jiraIssue->getStatus()->getId() !== $this->statusId
        ) {
            $this->jiraIssueRepository->transitionIssueTo(
                $jiraIssue->getKey(),
                new JiraTransition(
                    $this->transitionId,
                    'JirHub transitioned this issue.'
                )
            );
        }
    }
}
