<?php

namespace App\Controller;

use App\Handler\GitHubHandler;
use JiraRestApi\JiraException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class IndexController extends Controller
{
    /**
     * @Route("/check", name="check_deployability", methods={"GET"})
     */
    public function checkAction(Request $request, GitHubHandler $gitHubHandler): Response
    {
        $branch = $request->get('branch');
        $env    = $request->get('env');
        $force  = null !== $request->get('force');

        return new Response($gitHubHandler->checkDeployability($branch, $env, null, $force));
    }

    /**
     * @Route("/apply", name="apply_labels", methods={"GET"})
     *
     * @throws JiraException
     */
    public function applyAction(Request $request, GitHubHandler $gitHubHandler): Response
    {
        $branch = $request->get('branch');
        $env    = $request->get('env');
        $force  = null !== $request->get('force');

        return new Response((int) $gitHubHandler->applyLabels($branch, $env, $force));
    }

    /**
     * @Route("/jira_webhook", name="jira_webhook", methods={"POST"})
     */
    public function jiraWebhookAction(Request $request, GitHubHandler $gitHubHandler): Response
    {
        $data   = json_decode($request->getContent(), true);
        $status = $data['issue']['fields']['status']['name'];
        $key    = $data['issue']['key'];

        if ($status === getenv('JIRA_STATUS_DONE')) {
            $pullRequest = $gitHubHandler->getOpenPullRequestFromJiraIssueKey($key);

            if (null !== $pullRequest) {
                $gitHubHandler->mergePullRequest($pullRequest);
            }
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/github_webhook", name="github_webhook", methods={"POST"})
     */
    public function githubWebhookAction(Request $request, LoggerInterface $logger): Response
    {
        $logger->info(json_encode($request->headers->all()));
        $logger->info($request->getContent());

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
