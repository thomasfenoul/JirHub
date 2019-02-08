<?php


namespace App\Event;


use App\Model\PullRequest;
use Symfony\Component\EventDispatcher\Event;

class LabelsAppliedEvent extends Event
{
    const NAME = 'events.pull_requests.labels_applied';

    /** @var PullRequest $pullRequest */
    protected $pullRequest;

    /** @var string $reviewEnvironment */
    protected $reviewEnvironment;

    /** @var string|null $jiraIssueKey */
    protected $jiraIssueKey;

    public function __construct(PullRequest $pullRequest, string $reviewEnvironment, ?string $jiraIssueKey)
    {
        $this->pullRequest = $pullRequest;
        $this->reviewEnvironment = $reviewEnvironment;
        $this->jiraIssueKey = $jiraIssueKey;
    }

    public function getPullRequest(): PullRequest
    {
        return $this->pullRequest;
    }

    public function getReviewEnvironment(): string
    {
        return $this->reviewEnvironment;
    }

    public function getJiraIssueKey(): ?string
    {
        return $this->jiraIssueKey;
    }
}