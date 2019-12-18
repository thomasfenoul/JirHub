<?php

namespace App\Handler\SynchronizationCommand;

use App\Model\JirHubTask;
use App\Repository\GitHub\Constant\PullRequestUpdatableFields;
use App\Repository\GitHub\PullRequestRepository;

final class UpdatePullRequestTitleCommand implements SynchronizationCommandInterface
{
    /** @var PullRequestRepository */
    private $pullRequestRepository;

    public function __construct(PullRequestRepository $pullRequestRepository)
    {
        $this->pullRequestRepository = $pullRequestRepository;
    }

    public function execute(JirHubTask $jirHubTask): void
    {
        if (null !== $jirHubTask->getJiraIssue()) {
            return;
        }

        $pullRequest = $jirHubTask->getGithubPullRequest();
        $title       = $pullRequest->getTitle();

        $regexPattern  = '/^\[(?<prefix>.*)\]/i';
        $betterPrTitle = null;

        $matches = [];
        preg_match($regexPattern, $title, $matches);

        $labels = [
            'Tech' => 'Tech',
            'bug'  => 'Fix',
        ];

        foreach ($labels as $label => $prefix) {
            if ($pullRequest->hasLabel($label) && empty($matches['prefix'])) {
                $betterPrTitle = sprintf('[%s] %s', $prefix, $title);
            } elseif (!$pullRequest->hasLabel($label) && $matches['prefix'] === $prefix) {
                $betterPrTitle = str_replace(sprintf('[%s] ', $prefix), '', $title);
            }
        }

        if (null !== $betterPrTitle) {
            $this->pullRequestRepository->update(
                $pullRequest,
                [PullRequestUpdatableFields::TITLE => $betterPrTitle]
            );
        }
    }
}
