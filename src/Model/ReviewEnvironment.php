<?php

namespace App\Model;

class ReviewEnvironment
{
    /** @var string */
    private $name;

    /** @var JirHubTask */
    private $jirHubTask;

    /** @var string */
    private $pullRequestTitle;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getJirHubTask(): ?JirHubTask
    {
        return $this->jirHubTask;
    }

    public function setJirHubTask(JirHubTask $jirHubTask): self
    {
        $this->jirHubTask = $jirHubTask;

        return $this;
    }

    public function getPullRequestTitle(): string
    {
        if (null === $this->pullRequestTitle) {
            $issueKey = '';

            if (null !== $this->jirHubTask->getJiraIssue()) {
                $issueKey = $this->jirHubTask->getJiraIssue()->getKey();
            }

            $this->pullRequestTitle = ucfirst(
                trim(str_ireplace($issueKey, '', $this->jirHubTask->getGithubPullRequest()->getTitle()), ' :|-')
            );
        }

        return $this->pullRequestTitle;
    }
}
