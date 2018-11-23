<?php

namespace App\Handler;

use JoliCode\Slack\ClientFactory;
use JoliCode\Slack\Api\Client as SlackClient;

class SlackHandler
{
    /** @var SlackClient */
    private $client;

    public function __construct()
    {
        $this->client = ClientFactory::create(getenv('SLACK_TOKEN'));
    }

    public function sendMessage(string $message, string $channel = '')
    {
        if ('' === $channel) {
            $channel = getenv('SLACK_DEV_CHANNEL');
        }
        $this->client->chatPostMessage([
            'username'   => 'JirHub',
            'text'       => $message,
            'icon_emoji' => ':partyparrot:',
            'channel'    => $channel,
        ]);
    }
}
