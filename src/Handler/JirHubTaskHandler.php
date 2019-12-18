<?php

namespace App\Handler;

use App\Exception\PullRequestNotFoundException;
use App\Exception\UnexpectedContentType;
use App\Helper\JiraHelper;
use App\Model\JirHubTask;
use App\Repository\Jira\JiraIssueRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class JirHubTaskHandler
{
    /** @var GitHubHandler */
    private $githubHandler;

    /** @var JiraIssueRepository */
    private $jiraIssueRepository;

    public function __construct(GitHubHandler $githubHandler, JiraIssueRepository $jiraIssueRepository)
    {
        $this->githubHandler       = $githubHandler;
        $this->jiraIssueRepository = $jiraIssueRepository;
    }

    /**
     * @throws UnexpectedContentType
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws InvalidArgumentException
     * @throws PullRequestNotFoundException
     */
    public function getJirHubTaskFromGithubWebhookData(array $webhookData): JirHubTask
    {
        $jiraIssue   = null;
        $pullRequest = $this->githubHandler->getPullRequestFromWebhookData($webhookData);
        $issueKey    = JiraHelper::extractIssueKeyFromString($pullRequest->getHeadRef())
            ?? JiraHelper::extractIssueKeyFromString($pullRequest->getTitle());

        if (null !== $issueKey) {
            $jiraIssue = $this->jiraIssueRepository->getIssue($issueKey);
        }

        return new JirHubTask($pullRequest, $jiraIssue);
    }
}
