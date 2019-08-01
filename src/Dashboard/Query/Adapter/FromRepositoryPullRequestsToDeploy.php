<?php

namespace App\Dashboard\Query\Adapter;

use App\Dashboard\Query\PullRequestsToDeploy;
use App\Helper\JiraHelper;
use App\Model\JiraIssue;
use App\Repository\GitHub\PullRequestRepository;
use App\Repository\GitHub\PullRequestSearchFilters;

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
