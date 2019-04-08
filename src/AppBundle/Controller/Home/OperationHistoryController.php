<?php

namespace AppBundle\Controller\Home;

use AppBundle\Controller\HomeController;

use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/operation-history")
 *
 * Class PlanningController
 * @package AppBundle\Controller
 */
class OperationHistoryController extends HomeController {

    /**
     * @Route("/", name="operationhistorypage")
     */
    public function mainAction()
    {
        return $this->render('home/operationHistory/index.html.twig', [
            'menuElements' => $this->getMenuParameters(),
            'menuMode' => "home",
            "isConnected" => !$this->getUser() == NULL,
        ]);
    }
}