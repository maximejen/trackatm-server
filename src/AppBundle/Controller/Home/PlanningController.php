<?php

namespace AppBundle\Controller\Home;

use AppBundle\Controller\HomeController;

use AppBundle\Entity\Operation;
use AppBundle\Form\OperationType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/planning")
 *
 * Class PlanningController
 * @package AppBundle\Controller
 */
class PlanningController extends HomeController {

    /**
     * @Route("/", name="planningpage", methods={"GET", "POST"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return Response|JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function mainAction(Request $request, SerializerInterface $serializer)
    {
        $em = $this->getDoctrine()->getManager();

        $cleaners = $em->getRepository('AppBundle:Cleaner')->findAll();
        $places = $em->getRepository('AppBundle:Place')->findAll();
        $customers = $em->getRepository('AppBundle:Customer')->findAll();

        $operation = new Operation();
        $form = $this->createForm(OperationType::class, $operation);

        $isConnected = !$this->getUser() == NULL;

        if ($request->isMethod('POST')) {
            if (!$isConnected)
                return new JsonResponse(['message' => "you need to be connected"], 403);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->get('doctrine.orm.entity_manager');

                $em->persist($operation);
                $em->flush();

                return new Response($serializer->serialize($operation, 'json', ['groups' => ['operation']]));
            }
            return new JsonResponse(['message' => "form is not valid"]);
        }

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