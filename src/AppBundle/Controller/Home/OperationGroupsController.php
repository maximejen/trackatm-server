<?php

namespace AppBundle\Controller\Home;

use AppBundle\Controller\HomeController;

use AppBundle\Entity\Operation;
use AppBundle\Form\OperationType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

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

        $operationGroups = ["Monday" => [], "Tuesday" => [], "Wednesday" => [], "Thursday" => [], "Friday" => [], "Saturday" => [], "Sunday" => []];
//        foreach ($operationGroups as &$day) {
//            foreach ($cleaners as $cleanerTmp) {
//                if (!$cleanerId || ($cleanerId && $cleanerTmp->getId() == $cleanerId))
//                    $day[$cleanerTmp->getId()] = [];
//                foreach ($customers as $customerTmp) {
//                    if (!$customerId || ($customerId && $customerTmp->getId() == $customerId))
//                        $day[$cleanerTmp->getId()][$customerTmp->getName()]['id'] = $customerTmp->getId();
//                }
//            }
//        }
        /** @var Operation $operation */
        foreach ($operations as $operation) {
            $operationGroups[$operation->getDay()][$operation->getCleaner()->getId()][$operation->getCustomer()->getName()]['id'] = $operation->getCustomer()->getId();
            $operationGroups[$operation->getDay()][$operation->getCleaner()->getId()][$operation->getCustomer()->getName()][] = $operation;
        }

        $nbOperationGroups = 0;
        foreach ($operationGroups as $cleanersInArray) {
            foreach ($cleanersInArray as $cleanerInArray) {
                $nbOperationGroups += count($cleanerInArray);
            }
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
     * @return RedirectResponse|Response
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
     * @Route("/operations/edit", name="operations_edit", methods={"GET", "POST"})
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function bulkEditAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ids = $request->query->get('ids');
        $ids = explode(",", $ids);
        if (count($ids) > 0) {
            $operation = $em->getRepository('AppBundle:Operation')->find($ids[0]);
        }
        $form = $this->createForm(OperationType::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            foreach ($ids as $id) {
                $ope = $em->getRepository('AppBundle:Operation')->find($id);
                $ope->setDay($operation->getDay());
                $ope->setCleaner($operation->getCleaner());
            }

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
            'form' => $form->createView(),
            'bulkEdit' => true
        ]));
    }

    /**
     * @Route("/operation/delete/{id}", name="operation_delete", methods={"GET", "POST"})
     * @param Request $request
     * @param Operation $operation
     * @return RedirectResponse|Response
     */
    public function removeAction(Request $request, Operation $operation)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($operation);
        $em->flush();
        return $this->redirectToRoute('operation_groups');
    }

    /**
     * @Route("/operations/delete", name="operations_delete", methods={"GET", "POST"})
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function multipleRemoveAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $ids = $request->query->get('ids');
        $ids = explode(",", $ids);
        foreach ($ids as $id) {
            $operation = $em->getRepository('AppBundle:Operation')->find($id);
            $em->remove($operation);
        }
        $em->flush();
        return $this->redirectToRoute('operation_groups');
    }

    /**
     * @Route("/operation/create", name="operation_create", methods={"GET", "POST"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return RedirectResponse|Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createAction(Request $request, SerializerInterface $serializer)
    {
        $em = $this->getDoctrine()->getManager();
        $search = $request->get('search');
        $customerId = $request->get('customer');
        $day = $request->query->get('day');

        $cleaners = $em->getRepository('AppBundle:Cleaner')->findAll();
        if ($search != null && $customerId == null)
            $places = $em->getRepository('AppBundle:Place')->findPlaceByName($search);
        else if ($search != null && $customerId != null)
            $places = $em->getRepository('AppBundle:Place')->findPlaceByCustomerAndName($customerId, $search);
        else if ($customerId != null && $search == null)
            $places = $em->getRepository('AppBundle:Place')->findPlaceByCustomer($customerId);
        else
            $places = $em->getRepository('AppBundle:Place')->findAll();
        $customers = $em->getRepository('AppBundle:Customer')->findAll();


        $operation = new Operation();
        if ($day) {
            $operation->setDay($day);
        }
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

        $generalParams = [
            'menuElements' => $this->getMenuParameters(),
            'menuMode' => "home",
            "isConnected" => !$this->getUser() == NULL,
        ];
        return $this->render(":home/operationGroups:create.html.twig", array_merge($generalParams, [
            'form' => $form->createView(),
            'places' => $places,
            'customerId' => $customerId,
            'customers' => $customers,
            'search' => $search,
            'userToken' => $this->getUser()->getToken(),
            'cleaners' => $cleaners
        ]));
    }
}