<?php

namespace App\Handler;

use App\Model\Github\PullRequest;
use App\Model\Slack\ValidationInProgress;
use GuzzleHttp\Client;

class SlackHandler
{
    /** @var Client */
    private $client;
    
    public function __construct(string $token)
    {
        $this->client = new Client(
            [
                'headers'  => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
            ]
        );
    }

    public function handleInteraction(array $body): array
    {
        if ($body['actions'][0]['action_id'] === 'assign-pull-request') {
            
            $value = json_decode($body['actions'][0]['value'], true);
            
            $this->client->postAsync(
                $body['actions'][0]['response_url'],
                [
                    'json' => array_merge(
                        ["replace_original" => true],
                        (new ValidationInProgress(
                            PullRequest::denormalize($value['pull_request']),
                            $value['validation_env'],
                            $value['jira_issue_key'],
                            $body['user']['username']
                        ))->normalize()
                    )
                ]
            );
        }

        return [];
    }
}
