<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        $isConnected = !$this->getUser() == NULL;
        return $this->render('home/index.html.twig', [
            'menuElements' => $this->getMenuParameters(),
            'menuMode' => "home",
            "isConnected" => $isConnected
        ]);
    }

    private function getMenuParameters()
    {
        return
            [
                0 => [
                    "text" => "Operations",
                    "path" => "homepage"
                ],
                1 => [
                    "text" => "Test",
                    "path" => "homepage"
                ]
            ];
    }
}
