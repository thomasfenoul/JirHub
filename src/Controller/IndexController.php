<?php

namespace App\Controller;

use App\Handler\GitHubHandler;
use App\Handler\SlackHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends Controller
{
    /**
     * @Route("/check", name="check_deployability", methods={"GET"})
     *
     * @return Response
     */
    public function checkAction(Request $request, GitHubHandler $gitHubHandler)
    {
        $branch = $request->get('branch');
        $env    = $request->get('env');

        return new Response($gitHubHandler->checkDeployability($branch, $env));
    }

    /**
     * @Route("/apply", name="apply_labels", methods={"GET"})
     *
     * @return Response
     */
    public function applyAction(Request $request, GitHubHandler $gitHubHandler)
    {
        $branch = $request->get('branch');
        $env    = $request->get('env');

        return new Response((int) $gitHubHandler->applyLabels($branch, $env));
    }

    /**
     * @Route("/jira_webhook", name="jira_webhook", methods={"POST"})
     *
     * @return Response
     */
    public function jiraWebhookAction(Request $request, GitHubHandler $gitHubHandler, SlackHandler $slackHandler)
    {
        $data   = json_decode($request->getContent(), true);
        $status = $data['issue']['fields']['status']['name'];
        $key    = $data['issue']['key'];

        if ($status === getenv('JIRA_MERGE_STATUS')) {
            $pullRequest = $gitHubHandler->getOpenPullRequestFromJiraIssueKey($key);

            if (null !== $pullRequest) {
                $mergeResult = $gitHubHandler->mergePullRequest($pullRequest['head']['ref']);

                if (true !== $mergeResult) {
                    $slackHandler->sendMessage($mergeResult, getenv('SLACK_DEV_CHANNEL'));
                    $gitHubHandler->removeReviewLabels($pullRequest);
                    $gitHubHandler->addLabelToPullRequest(getenv('GITHUB_REVIEW_OK_LABEL'), $pullRequest['number']);
                }
            }
        }

        return new Response();
    }
}
