<?php

namespace App\Dashboard\Query\Adapter;

use App\Dashboard\Query\PullRequestsToDeploy;
use App\Repository\GitHub\PullRequestRepository;
use App\Repository\GitHub\PullRequestSearchFilters;

class FromGitHubHandlerPullRequestsToDeploy implements PullRequestsToDeploy
{
    /** @var PullRequestRepository */
    protected $pullRequestRepository;

    public function __construct(PullRequestRepository $pullRequestRepository)
    {
        $this->pullRequestRepository = $pullRequestRepository;
    }

    public function fetch(): array
    {
        return $this->pullRequestRepository->search(
            [
                PullRequestSearchFilters::STATE            => 'open',
                PullRequestSearchFilters::RESULTS_PER_PAGE => 50,
                PullRequestSearchFilters::LABELS           => ['~validation-required'],
            ]
        );
    }
}
