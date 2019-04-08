<?php

namespace AppBundle\Controller\Home;

use AppBundle\Controller\HomeController;

use AppBundle\Form\OperationType;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/planning")
 *
 * Class PlanningController
 * @package AppBundle\Controller
 */
class PlanningController extends HomeController {

    /**
     * @Route("/", name="planningpage")
     */
    public function mainAction()
    {
        $em = $this->getDoctrine()->getManager();

        $cleaners = $em->getRepository('AppBundle:Cleaner')->findAll();
        $places = $em->getRepository('AppBundle:Place')->findAll();
        $customers = $em->getRepository('AppBundle:Customer')->findAll();

        $form = $this->createForm(OperationType::class, null);

        $isConnected = !$this->getUser() == NULL;
        return $this->render('home/planning/index.html.twig', [
            'menuElements' => $this->getMenuParameters(),
            'menuMode' => "home",
            "isConnected" => $isConnected,
            "cleaners" => $cleaners,
            "places" => $places,
            "customers" => $customers,
            "form" => $form->createView(),
        ]);
    }
}