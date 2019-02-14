<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ParametersController extends Controller
{
    /**
     * @Route("/parameters", name="parameters_page")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->redirect($this->generateUrl('parameters_customers_page'));
    }

    protected function getCommonParametersForParameters()
    {
        $isConnected = !$this->getUser() == NULL;
        return
            [
                'menuElements' => [
                    0 => [
                        "text" => "Customers",
                        "path" => "parameters_customers_page"
                    ],
                    1 => [
                        "text" => "Places",
                        "path" => "parameters_places_page"
                    ],
                    2 => [
                        "text" => "Cleaners",
                        "path" => "parameters_cleaners_page"
                    ],
                    3 => [
                        "text" => "Users",
                        "path" => "parameters_users_page"
                    ]
                ],
                'menuMode' => "parameters",
                "isConnected" => $isConnected,
            ];
    }
}
