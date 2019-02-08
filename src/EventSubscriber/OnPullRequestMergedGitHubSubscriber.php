<?php

namespace App\EventSubscriber;

use App\Event\PullRequestMergedEvent;
use App\Handler\GitHubHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OnPullRequestMergedGitHubSubscriber implements EventSubscriberInterface
{
    /** @var GitHubHandler $gitHubHandler */
    protected $gitHubHandler;

    public function __construct(GitHubHandler $gitHubHandler)
    {
        $this->gitHubHandler = $gitHubHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            PullRequestMergedEvent::NAME => 'updatePullRequest'
        ];
    }


    public function updatePullRequest(PullRequestMergedEvent $event)
    {
        try {
            $this->gitHubHandler->removeReviewLabels($event->getPullRequest());
            $this->gitHubHandler->addLabelToPullRequest(getenv('GITHUB_REVIEW_OK_LABEL'), $event->getPullRequest());
        } catch (\Throwable $t) {

        }
    }
}