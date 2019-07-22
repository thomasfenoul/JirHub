<?php

namespace App\Controller;

use App\Handler\GitHubHandler;
use App\Repository\GitHub\PullRequestRepository;
use JiraRestApi\JiraException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends Controller
{
    /**
     * @Route("/check", name="check_deployability", methods={"GET"})
     */
    public function checkAction(Request $request, GitHubHandler $gitHubHandler): Response
    {
        $branch = $request->get('branch');
        $env    = $request->get('env');

        return new JsonResponse(
            [
                'message' => $gitHubHandler->checkDeployability($branch, $env, null),
            ]
        );
    }

    /**
     * @Route("/apply", name="apply_labels", methods={"GET"})
     *
     * @throws JiraException
     */
    public function applyAction(Request $request, GitHubHandler $gitHubHandler, LoggerInterface $logger): Response
    {
        $branch = $request->get('branch');
        $env    = $request->get('env');

        try {
            $gitHubHandler->applyLabels($branch, $env);
        } catch (\Exception $e){
            $logger->error($e->getMessage());
        }

        return new Response((int) $gitHubHandler->applyLabels($branch, $env));
    }

    /**
     * @Route("/jira_webhook", name="jira_webhook", methods={"POST"})
     */
    public function jiraWebhookAction(Request $request, PullRequestRepository $pullRequestRepository): Response
    {
        $data   = json_decode($request->getContent(), true);
        $status = $data['issue']['fields']['status']['name'];
        $key    = $data['issue']['key'];

        if ($status === getenv('JIRA_STATUS_DONE')) {
            $pullRequest = array_pop($pullRequestRepository->search(['head_ref' => $key]));

            if (null === $pullRequest) {
                $pullRequest = array_pop($pullRequestRepository->search(['title' => $key]));
            }

            if (null !== $pullRequest) {
                $pullRequestRepository->merge($pullRequest);
            }
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/github_webhook", name="github_webhook", methods={"POST"})
     *
     * @throws JiraException
     */
    public function githubWebhookAction(GitHubHandler $gitHubHandler): Response
    {
        $gitHubHandler->synchronize();

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
