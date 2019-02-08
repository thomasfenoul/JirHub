<?php

namespace App\EventSubscriber;

use App\Event\LabelsAppliedEvent;
use App\Exception\PullRequestMergeFailure;
use App\Handler\JiraHandler;
use JoliCode\Slack\Api\Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SlackSubscriber implements EventSubscriberInterface
{
    /** @var Client */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION  => 'onKernelException',
            LabelsAppliedEvent::NAME => 'onLabelsApplied'
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($event->getException() instanceof PullRequestMergeFailure) {
            try {
                $this->sendMessage(
                    sprintf(
                        'JirHub could not merge this pull request : %s \nError : %s',
                        $event->getException()->getPullRequest()->getUrl(),
                        $event->getException()->getPrevious()->getMessage()
                    ),
                    getenv('SLACK_DEV_CHANNEL')
                );
            } catch (\Throwable $t) {

            }
        }
    }

    public function onLabelsApplied(LabelsAppliedEvent $event)
    {
        try {
            $subject = $event->getReviewEnvironment();
            $blame   = '(demander à ' . $event->getPullRequest()->getUser() . ' de retrouver la tâche Jira)';

            if (null !== $event->getJiraIssueKey()) {
                $subject = JiraHandler::buildIssueUrlFromIssueName($event->getJiraIssueKey());
                $blame = '';
            }

            $this->sendMessage(
                sprintf(
                    "%s %s dispo sur `%s` %s\n Pull Request : %s",
                    getenv('SLACK_LINK_TAG'),
                    $subject,
                    $event->getReviewEnvironment(),
                    $blame,
                    $event->getPullRequest()->getUrl()
                ),
                getenv('SLACK_REVIEW_CHANNEL')
            );
        } catch (\Throwable $t) {

        }
    }

    protected function sendMessage(string $message, string $channel = '')
    {
        if ('' === $channel) {
            $channel = getenv('SLACK_DEV_CHANNEL');
        }
        $this->client->chatPostMessage([
            'username'   => 'JirHub',
            'text'       => $message,
            'icon_emoji' => ':eyes:',
            'channel'    => $channel,
        ]);
    }
}