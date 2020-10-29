<?php

namespace App\Handler;

use App\Model\Github\PullRequest;
use App\Model\Slack\ValidationInProgress;

class SlackHandler
{
    public function handleInteraction(array $body): array
    {
        if ($body['actions'][0]['action_id'] === 'assign-pull-request') {
            
            $value = json_decode($body['actions'][0]['value'], true);
            
            return array_merge(
                ["replace_original" => true],
                (new ValidationInProgress(
                    PullRequest::denormalize($value['pull_request']),
                    $value['validation_env'],
                    $value['jira_issue_key'],
                    $body['user']['username']
                ))->normalize()
            );
        }

        return [];
    }
}
