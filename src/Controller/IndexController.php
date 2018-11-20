<?php

namespace App\Controller;

use App\Handler\GitHubHandler;
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
}
