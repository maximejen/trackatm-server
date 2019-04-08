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
        return $this->redirect($this->generateUrl('planningpage'));
    }

    protected function getMenuParameters()
    {
        return
            [
                0 => [
                    "text" => "Planning",
                    "path" => "planningpage"
                ],
                1 => [
                    "text" => "Operation History",
                    "path" => "operationhistorypage"
                ],
            ];
    }
}
