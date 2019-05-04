<?php

namespace AppBundle\Controller\Home;

use AppBundle\Controller\HomeController;

use AppBundle\Entity\Operation;
use AppBundle\Form\OperationType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/operation-groups")
 *
 * Class PlanningController
 * @package AppBundle\Controller
 */
class OperationGroupsController extends HomeController {

    /**
     * @Route("/", name="operation_groups", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function mainAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $day = $request->query->get('day');
        $customerId = $request->query->get("customer");
        if ($customerId != null && $customerId != '')
            $customer = $em->getRepository("AppBundle:Customer")->find($customerId);
        else
            $customer = null;
        $cleanerId = $request->query->get("cleaner");
        if ($cleanerId != null && $cleanerId != '')
            $cleaner = $em->getRepository("AppBundle:Cleaner")->find($cleanerId);
        else
            $cleaner = null;

        $operations = $em->getRepository("AppBundle:Operation")->findAll();

        $operations = array_filter($operations, function(Operation $a) use ($cleaner, $customer, $day) {
            $result = true;
            if ($customer && $a->getPlace()->getCustomer()->getId() != $customer->getId()) {
                $result = false;
            }
            if ($result && $cleaner && $a->getCleaner()->getId() != $cleaner->getId()) {
                $result = false;
            }
            if ($result && $day && $day != $a->getDay()) {
                $result = false;
            }
            return $result;
        });

        $customers = $em->getRepository("AppBundle:Customer")->findAll();
        $cleaners = $em->getRepository("AppBundle:Cleaner")->findAll();

        $nbOperationGroups = count($customers) * count($cleaners) * 7;

        $operationGroups = [];
        /** @var Operation $operation */
        foreach ($operations as $operation) {
            $operationGroups[$operation->getDay()][$operation->getCleaner()->getId()][$operation->getCustomer()->getName()][] = $operation;
        }

        $generalParams = [
            'menuElements' => $this->getMenuParameters(),
            'menuMode' => "home",
            "isConnected" => !$this->getUser() == NULL,
        ];
        return $this->render("home/operationGroups/index.html.twig", array_merge($generalParams, [
            'operationGroups' => $operationGroups,
            'nbOperationGroups' => $nbOperationGroups,
            'customers' => $customers,
            'cleaners' => $cleaners,
            'selectedCustomer' => $customer,
            'selectedCleaner' => $cleaner,
            'selectedDay' => $day
        ]));
    }

    /**
     * @Route("/group", name="operation_group", methods={"GET"})
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function viewAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $day = $request->query->get('day');
        $customerId = $request->query->get("customer");
        $cleanerId = $request->query->get("cleaner");

        if (!$day || !$customerId || !$cleanerId)
            return $this->redirectToRoute('operation_groups');

        $customer = $em->getRepository("AppBundle:Customer")->find($customerId);
        $cleaner = $em->getRepository("AppBundle:Cleaner")->find($cleanerId);

        $operations = $em->getRepository('AppBundle:Operation')->getOperationsByGroup($day, $customerId, $cleanerId);

        $generalParams = [
            'menuElements' => $this->getMenuParameters(),
            'menuMode' => "home",
            "isConnected" => !$this->getUser() == NULL,
        ];
        return $this->render("home/operationGroups/view.html.twig", array_merge($generalParams, [
            'day' => $day,
            'customer' => $customer,
            'cleaner' => $cleaner,
            'operations' => $operations
        ]));
    }

    /**
     * @Route("/operation/edit/{id}", name="operation_edit", methods={"GET", "POST"})
     * @param Request $request
     * @param Operation $operation
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction(Request $request, Operation $operation)
    {
        $form = $this->createForm(OperationType::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            return $this->redirectToRoute('operation_group', [
                'day' => $operation->getDay(),
                'customer' => $operation->getPlace()->getCustomer()->getId(),
                'cleaner' => $operation->getCleaner()->getId()
            ]);
        }

        $generalParams = [
            'menuElements' => $this->getMenuParameters(),
            'menuMode' => "home",
            "isConnected" => !$this->getUser() == NULL,
        ];
        return $this->render(":home/operationGroups:edit.html.twig", array_merge($generalParams, [
            'form' => $form->createView()
        ]));
    }

    /**
     * @Route("/operation/edit/{id}", name="operation_delete", methods={"GET", "POST"})
     * @param Request $request
     * @param Operation $operation
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function removeAction(Request $request, Operation $operation)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($operation);
        $em->flush();
        return $this->redirectToRoute('operation_groups');
    }
}