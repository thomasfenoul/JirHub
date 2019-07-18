<?php

namespace App\Dashboard\Query\Adapter;

use App\Dashboard\Query\ReviewEnvironments;
use App\Handler\GitHubHandler;
use App\Helper\JiraHelper;
use App\Model\ReviewEnvironment;
use App\Repository\GitHub\PullRequestRepository;
use App\Repository\GitHub\PullRequestSearchFilters;

class FromGitHubHandlerReviewEnvironments implements ReviewEnvironments
{
    /** @var GitHubHandler */
    protected $handler;

    /** @var PullRequestRepository */
    protected $pullRequestRepository;

    public function __construct(GitHubHandler $handler, PullRequestRepository $pullRequestRepository)
    {
        $this->handler               = $handler;
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
                [
                    PullRequestSearchFilters::STATE            => 'open',
                    PullRequestSearchFilters::RESULTS_PER_PAGE => 50,
                    PullRequestSearchFilters::LABELS           => ['~validation-' . $environment->getName()],
                ]
            );

            if (!empty($pullRequestsOnEnvironment)) {
                $pullRequest = $this->pullRequestRepository->fetch(array_pop($pullRequestsOnEnvironment)->getNumber());

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
