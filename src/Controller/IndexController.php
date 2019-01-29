<?php

namespace App\Controller;

use App\Handler\GitHubHandler;
use App\Handler\JiraHandler;
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
        $force  = null !== $request->get('force');

        return new Response($gitHubHandler->checkDeployability($branch, $env, null, $force));
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
        $force  = null !== $request->get('force');

        return new Response((int) $gitHubHandler->applyLabels($branch, $env, $force));
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

        if ($status === getenv('JIRA_STATUS_DONE')) {
            $pullRequest = $gitHubHandler->getOpenPullRequestFromJiraIssueKey($key);

            if (null !== $pullRequest) {
                $mergeResult = $gitHubHandler->mergePullRequest($pullRequest);

                if (true !== $mergeResult) {
                    $slackHandler->sendMessage($mergeResult, getenv('SLACK_DEV_CHANNEL'));
                    $gitHubHandler->removeReviewLabels($pullRequest);
                    $gitHubHandler->addLabelToPullRequest(getenv('GITHUB_REVIEW_OK_LABEL'), $pullRequest);
                }
            }
        }

        return new Response();
    }

    /**
     * @Route("/test_j", name="test_j", methods={"GET"})
     *
     * @return Response
     */
    public function testJ(JiraHandler $jiraHandler)
    {
        return new Response(json_encode($jiraHandler->getIssue('TA-1603')));
    }
}
