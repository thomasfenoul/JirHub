<?php

namespace App\Handler\SynchronizationCommand;

use App\Model\JirHubTask;
use App\Repository\GitHub\Constant\PullRequestUpdatableFields;
use App\Repository\GitHub\PullRequestRepository;

final class UpdatePullRequestDescriptionCommand implements SynchronizationCommandInterface
{
    /** @var PullRequestRepository */
    private $pullRequestRepository;

    public function __construct(PullRequestRepository $pullRequestRepository)
    {
        $this->pullRequestRepository = $pullRequestRepository;
    }

    public function execute(JirHubTask $jirHubTask): void
    {
        $pullRequestBody = $jirHubTask->getGithubPullRequest()->getBody();
        $bodyPrefix      = '> Cette _pull request_ a été ouverte sans ticket Jira associé 👎';

        if (null !== $jirHubTask->getJiraIssue()) {
            $bodyPrefix = $jirHubTask->getJiraIssue()->getUri()->__toString();
        }

        if (false === strpos($pullRequestBody, $bodyPrefix)) {
            $this->pullRequestRepository->update(
                $jirHubTask->getGithubPullRequest(),
                [PullRequestUpdatableFields::BODY => $bodyPrefix . "\n\n" . $pullRequestBody]
            );
        }
    }
}
