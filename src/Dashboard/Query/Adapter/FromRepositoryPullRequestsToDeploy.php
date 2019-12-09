<?php

namespace App\Dashboard\Query\Adapter;

use App\Dashboard\Query\PullRequestsToDeploy;
use App\Repository\GitHub\Constant\PullRequestSearchFilters;
use App\Repository\GitHub\PullRequestRepository;

class FromRepositoryPullRequestsToDeploy implements PullRequestsToDeploy
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
            [PullRequestSearchFilters::LABELS => ['~validation-required']]
        );
    }
}
