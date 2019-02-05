<?php

namespace App\Dashboard\Handler;

use App\Dashboard\Query\PullRequestsToDeploy;
use App\Dashboard\Query\PullRequestsToMergeOnDev;
use App\Dashboard\Query\ReviewEnvironments;

class DashboardHandler
{
    const REVIEW_ENVIRONMENTS = 'review_environments';

    const PULL_REQUEST_TO_DEPLOY = 'pull_requests_to_deploy';

    const PULL_REQUEST_TO_MERGE_ON_DEV = 'pull_requests_to_merge_on_dev';

    /** @var ReviewEnvironments */
    protected $reviewEnvironments;

    /** @var PullRequestsToDeploy */
    protected $pullRequestsToDeploy;

    /** @var PullRequestsToMergeOnDev */
    protected $pullRequestsToMergeOnDev;


    public function __construct(
        ReviewEnvironments $reviewEnvironments,
        PullRequestsToDeploy $pullRequestsToDeploy,
        PullRequestsToMergeOnDev $pullRequestsToMergeOnDev
    ) {
        $this->reviewEnvironments = $reviewEnvironments;
        $this->pullRequestsToDeploy = $pullRequestsToDeploy;
        $this->pullRequestsToMergeOnDev = $pullRequestsToMergeOnDev;
    }

    public function getData()
    {
        return [
            self::REVIEW_ENVIRONMENTS => $this->reviewEnvironments->fetch(),
            self::PULL_REQUEST_TO_DEPLOY => $this->pullRequestsToDeploy->fetch(),
            self::PULL_REQUEST_TO_MERGE_ON_DEV => $this->pullRequestsToMergeOnDev->fetch(),
        ];
    }
}