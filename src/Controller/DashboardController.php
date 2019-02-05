<?php

namespace App\Controller;

use App\Dashboard\Handler\DashboardHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends Controller
{
    /** @var DashboardHandler */
    protected $handler;

    /**
     * DashboardController constructor.
     * @param DashboardHandler $handler
     */
    public function __construct(DashboardHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/dashboard", name="get_dashboard", methods={"GET"})
     */
    public function index()
    {
        return $this->render('dashboard/index.html.twig', $this->handler->getData());
    }
}