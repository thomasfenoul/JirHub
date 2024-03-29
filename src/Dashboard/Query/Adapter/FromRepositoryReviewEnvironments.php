<?php

namespace App\Dashboard\Query\Adapter;

use App\Dashboard\Query\ReviewEnvironments;
use App\Helper\JiraHelper;
use App\Model\ReviewEnvironment;
use App\Repository\GitHub\PullRequestRepository;
use App\Repository\GitHub\PullRequestSearchFilters;

class FromRepositoryReviewEnvironments implements ReviewEnvironments
{
    /** @var PullRequestRepository */
    protected $pullRequestRepository;

    public function __construct(PullRequestRepository $pullRequestRepository)
    {
        $this->pullRequestRepository = $pullRequestRepository;
    }

    public function fetch(): array
    {
        $environments = [
            new ReviewEnvironment('red'),
            new ReviewEnvironment('blue'),
            new ReviewEnvironment('green'),
            new ReviewEnvironment('yellow'),
        ];

        /** @var ReviewEnvironment $environment */
        foreach ($environments as $environment) {
            $pullRequestsOnEnvironment = $this->pullRequestRepository->search(
                [PullRequestSearchFilters::LABELS => ['~validation-' . $environment->getName()]]
            );

            if (!empty($pullRequestsOnEnvironment)) {
                $pullRequest = array_pop($pullRequestsOnEnvironment);

                $environment
                    ->setPullRequest($pullRequest)
                    ->setJiraIssueKey(
                        JiraHelper::extractIssueKeyFromString($pullRequest->getHeadRef())
                        ?? JiraHelper::extractIssueKeyFromString($pullRequest->getTitle())
                    )
                ;
            }
        }

        return $environments;
    }
}
