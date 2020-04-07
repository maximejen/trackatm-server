<?php

namespace AppBundle\Controller\Api;

use AppBundle\Entity\Cleaner;
use AppBundle\Entity\Operation;
use AppBundle\Entity\OperationHistory;
use AppBundle\Form\OperationType;
use DateInterval;
use DatePeriod;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class OperationController
 * @package AppBundle\Controller\Api
 */
class OperationController extends ApiController
{
    /**
     * @Rest\View(serializerGroups={"operation"})
     * @Rest\Get("/api/operations/")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return Response
     */
    public function getOperationsAction(Request $request, SerializerInterface $serializer)
    {
        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);
        $operations = $this->get('doctrine.orm.entity_manager')
            ->getRepository('AppBundle:Operation')
            ->findAll();

        return new Response($serializer->serialize($operations, 'json', ['groups' => ['operation']]));
    }

    private function getWeek($operations)
    {
        $week = ["Monday" => [], "Tuesday" => [], "Wednesday" => [], "Thursday" => [], "Friday" => [], "Saturday" => [], "Sunday" => []];
        foreach ($operations as $key => $operation)
            $week[ucfirst(strtolower($operation->getDay()))][] = $operation;
        return $week;
    }

    private function getOperationsPlanning($date1, $date2, $week)
    {
        $copy1 = clone $date1;
        $copy2 = clone $date2;
        $copy1->setTime(0, 0, 0);
        $copy2->setTime(0, 0, 0);
        $period = new DatePeriod(
            $copy1,
            new DateInterval('P1D'),
            $copy2->modify("+1 days")
        );
        $planning = [];
        foreach ($period as $key => $value) {
            $planning[$value->format('Y-m-d')] = $week[$value->format("l")];
        }
        return $planning;
    }

    private function fromPlanningToFlat($planning)
    {
        $newArray = [];
        foreach ($planning as $item) {
            foreach ($item as $value) {
                $newArray[] = $value;
            }
        }

        return $newArray;
    }

    private function determineOperationsDone($histories, $planning)
    {
        $nbTimesToBeDone = [];
        $nbTimesDone = [];
        /** @var OperationHistory $history */
        foreach ($histories as $history) {
            if (!array_key_exists($history->getPlace(), $nbTimesDone))
                $nbTimesDone[$history->getPlace()] = 0;
            $nbTimesDone[$history->getPlace()]++;
        }
        foreach ($planning as $date) {
            /** @var Operation $operation */
            foreach ($date as $operation) {
                if (!array_key_exists($operation->getPlace()->getName(), $nbTimesToBeDone))
                    $nbTimesToBeDone[$operation->getPlace()->getName()] = 0;
                $nbTimesToBeDone[$operation->getPlace()->getName()]++;
            }
        }
        foreach ($planning as &$date) {
            /** @var Operation $operation */
            foreach ($date as &$operation) {
                if (array_key_exists($operation->getPlace()->getName(), $nbTimesDone) && $nbTimesDone[$operation->getPlace()->getName()] > 0) {
                    $operation->setDone(true);
                    $nbTimesDone[$operation->getPlace()->getName()]--;
                } else
                    $operation->setDone(false);
            }
        }
        return $planning;
    }

    private function filterOperations($operations, $operationsThisWeek, &$operationsCounter) {

        // Filter all the operations that were already done the number asked when they were created.
        return array_filter($operations, function (Operation $element) use ($operationsThisWeek, &$operationsCounter) {
            $nbFromCustomer = $element->getCustomer()->getNumberMaxOfOperations();
            $nbFromOperation = $element->getNumberMaxPerMonth();
            $nbMax = $nbFromCustomer ? $nbFromCustomer : null;
            $nbMax = $nbMax == null ? ($nbFromOperation ? $nbFromOperation : null) : $nbMax;
            if (is_int($nbFromCustomer) && is_int($nbFromOperation)) {
                $nbMax = $nbFromCustomer > $nbFromOperation ? $nbFromOperation : $nbFromCustomer;
            }
            if ($nbMax == null)
                return (true);
            $index = $element->getPlace()->getName() . $element->getCleaner()->__toString() . $element->getTemplate()->getName();
            if (is_int($nbMax) && (array_key_exists($index, $operationsThisWeek) && count($operationsThisWeek[$index]) >= $nbMax)) {
                return (false);
            }
            $index = $element->getPlace()->getName();
            if ($operationsCounter != null && array_key_exists($index, $operationsCounter) && !is_null($operationsCounter[$index])) {
                if ($operationsCounter[$index] <= 0)
                    return (false);
                $operationsCounter[$index]--;
            }
            return (true);
        });
    }

    function EqualReferences(&$first, &$second){
        if($first !== $second){
            return false;
        }
        $value_of_first = $first;
        $first = ($first === true) ? false : true; // modify $first
        $is_ref = ($first === $second); // after modifying $first, $second will not be equal to $first, unless $second and $first points to the same variable.
        $first = $value_of_first; // unmodify $first
        return $is_ref;
    }

    /**
     * @Rest\View(serializerGroups={"operation"})
     * @Rest\Get("/api/cleaner/operations/")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     *
     * @return JsonResponse|Response
     * @throws \Exception
     */
    public function getOperationsOfCleaner(Request $request, SerializerInterface $serializer)
    {
        $user = $this->checkUserIsConnected($request);
        if (!$user)
            return new JsonResponse(['message' => "you need to be connected"], 403);

        // get actual week limits
        $weekStart = new \DateTime();
        $weekEnd = new\DateTime();
        if ($weekStart->format("l") != "Sunday")
            $weekStart->modify('last sunday');
        if ($weekEnd->format("l") != "Saturday")
            $weekEnd->modify('next saturday');



        // get next week limits
        $nextWeekStart = new \DateTime();
        $nextWeekEnd = new \DateTime();
        $nextWeekStart->modify("next Sunday");
        $nextWeekEnd->modify("next Sunday");
        $nextWeekEnd->modify("next Saturday");


        // get last week limits
        $lastWeekStart = new \DateTime($weekStart->format("Y-m-d"));
        $lastWeekEnd = new \DateTime($weekEnd->format("Y-m-d"));
        $lastWeekStart->modify("-7 days");
        $lastWeekEnd->modify("-7 days");


        // get limits of the week before the last one
        $lastLastWeekStart = new \DateTime($lastWeekStart->format("Y-m-d"));
        $lastLastWeekEnd = new \DateTime($lastWeekEnd->format("Y-m-d"));
        $lastLastWeekStart->modify("-7 days");
        $lastLastWeekEnd->modify("-7 days");

        $em = $this->getDoctrine()->getManager();
        $cleaner = $em->getRepository('AppBundle:Cleaner')->findByUser($user);

        ///

        $operationHistoriesLastLastWeek = $em->getRepository("AppBundle:OperationHistory")->findOperationHistoriesByCleanerThisMonth($cleaner, $lastLastWeekStart, $lastLastWeekEnd);
        $operationHistoriesLastWeek = $em->getRepository("AppBundle:OperationHistory")->findOperationHistoriesByCleanerThisMonth($cleaner, $lastWeekStart, $lastWeekEnd);
        $operationHistoriesWeek = $em->getRepository("AppBundle:OperationHistory")->findOperationHistoriesByCleanerThisMonth($cleaner, $weekStart, $weekEnd);
        $operationHistoriesNextWeek = $em->getRepository("AppBundle:OperationHistory")->findOperationHistoriesByCleanerThisMonth($cleaner, $nextWeekStart, $nextWeekEnd);

        // get the operations that will need to be filtered
        $allOperations = $em->getRepository('AppBundle:Operation')->findOperationsByCleaner($cleaner);


        $operationCounter = [];
        // this part is counting the number of times an operation should show per month.
        /** @var Operation $operation */
        foreach ($allOperations as $operation) {
            $nbFromCustomer = $operation->getCustomer()->getNumberMaxOfOperations();
            $nbFromOperation = $operation->getNumberMaxPerMonth();
            $nbMax = $nbFromCustomer ? $nbFromCustomer : null;
            $nbMax = $nbMax == null ? ($nbFromOperation ? $nbFromOperation : null) : $nbMax;
            $operationCounter[$operation->getPlace()->getName()] = $nbMax;
        }

        $operations = [];
        foreach ($allOperations as $operation) {
            $operations[] = clone($operation);
        }
        $operations1 = [];
        foreach ($allOperations as $operation) {
            $operations1[] = clone($operation);
        }
        $operations2 = [];
        foreach ($allOperations as $operation) {
            $operations2[] = clone($operation);
        }
        $operations3 = [];
        foreach ($allOperations as $operation) {
            $operations3[] = clone($operation);
        }

        $null = null;

        // Filter all the operations that were already done the number asked when they were created per week
        $operations = $this->filterOperations($operations, $operationHistoriesLastLastWeek, $null);
        $operations1 = $this->filterOperations($operations1, $operationHistoriesLastWeek, $null);
        $operations2 = $this->filterOperations($operations2, $operationHistoriesWeek, $operationCounter);
//        var_dump($operationCounter);
        $operations3 = $this->filterOperations($operations3, $operationHistoriesNextWeek, $operationCounter);
//        var_dump($operationCounter);

        $historiesLastLastWeek = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesByCleanerAndBetweenTwoDates($cleaner, $lastLastWeekStart, $lastLastWeekEnd);
        $historiesLastWeek = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesByCleanerAndBetweenTwoDates($cleaner, $lastWeekStart, $lastWeekEnd);
        $historiesActualWeek = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesByCleanerAndBetweenTwoDates($cleaner, $weekStart, $weekEnd);
        $historiesNextWeek = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesByCleanerAndBetweenTwoDates($cleaner, $nextWeekStart, $nextWeekEnd);


        $lastLastWeek = $this->getWeek($operations);
        $lastWeek = $this->getWeek($operations1);
        $week = $this->getWeek($operations2);
        $nextWeek = $this->getWeek($operations3);

        $lastLastWeekPlanning = $this->getOperationsPlanning($lastLastWeekStart, $lastLastWeekEnd, $lastLastWeek);
        $lastWeekPlanning = $this->getOperationsPlanning($lastWeekStart, $lastWeekEnd, $lastWeek);
        $planning = $this->getOperationsPlanning($weekStart, $weekEnd, $week);
        $nextPlanning = $this->getOperationsPlanning($nextWeekStart, $nextWeekEnd, $nextWeek);

        $flat = $request->query->get('flat') == null ? "true" : $request->query->get('flat');

        if ($flat == "true") {
            foreach ($allOperations as $operation) $operation->setDone(false); // all operations are on false when it's flat for compatibility reasons.
            $operations = $this->fromPlanningToFlat($planning);
            return new Response($serializer->serialize($operations, 'json', ['groups' => ['operation']]));
        } else {
            $lastLastWeekPlanning = $this->determineOperationsDone($historiesLastLastWeek, $lastLastWeekPlanning);
            $lastWeekPlanning = $this->determineOperationsDone($historiesLastWeek, $lastWeekPlanning);
            $planning = $this->determineOperationsDone($historiesActualWeek, $planning);
            $nextPlanning = $this->determineOperationsDone($historiesNextWeek, $nextPlanning);


            $planning = array_merge($lastLastWeekPlanning, $lastWeekPlanning, $planning, $nextPlanning);


            return new Response($serializer->serialize($planning, 'json', ['groups' => ['operation']]));
        }
    }

    // this code is to make all the ATM of today available and disappearing if they are done today.
    //            $today = new \DateTime();
    //            $historiesOfToday = array_filter($histories, function($history) use($today) {
    //                if ($history->getBeginningDate()->format('Y-m-d') == $today->format('Y-m-d'))
    //                    return true;
    //                return false;
    //            });
    //            array_walk($planning[$today->format('Y-m-d')], function($operation) use($historiesOfToday) {
    //                $operation->setDone(false);
    //                /** @var OperationHistory $history */
    //                foreach ($historiesOfToday as $history) {
    //                    if ($operation->getPlace()->getName() == $history->getPlace())
    //                        $operation->setDone(true);
    //                }
    //            });

    /**
     * @Rest\View(serializerGroups={"operation"})
     * @Rest\Get("/api/operation/{id}")
     *
     * @param Request $request
     * @param Operation $operation
     * @param SerializerInterface $serializer
     * @return Response
     */
    public function getOperationAction(Request $request, Operation $operation, SerializerInterface $serializer)
    {
        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);
        return new Response($serializer->serialize($operation, 'json', ['groups' => ['operation']]));
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_CREATED)
     * @Rest\Post("/api/operations")
     *
     * @param Request $request
     *
     * @param SerializerInterface $serializer
     * @return Response
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function postOperationsAction(Request $request, SerializerInterface $serializer)
    {
        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);
        $operation = new Operation();
        $form = $this->createForm(OperationType::class, $operation);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->get('doctrine.orm.entity_manager');

            $em->persist($operation);
            $em->flush();

            return new Response($serializer->serialize($operation, 'json', ['groups' => ['operation']]));
        } else {
            return new JsonResponse([
                'message' => 'too bad',
                'submitted' => $form->isSubmitted(),
                'valid' => $form->isValid(),
                'errors' => $form->getErrors(),
                'day' => $form->getData()->getDay(),
                'place' => $form->getData()->getPlace(),
                'template' => $form->getData()->getTemplate(),
                'cleaner' => $form->getData()->getCleaner(),
                'request' => $request->request,
            ], 400);
        }
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_NO_CONTENT)
     * @Rest\Delete("/api/operations/{id}")
     *
     * @param Request $request
     *
     * @param SerializerInterface $serializer
     * @return JsonResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeOperationAction(Request $request, SerializerInterface $serializer)
    {
        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);
        $em = $this->get('doctrine.orm.entity_manager');
        /* @var $operation Operation */
        $operation = $em
            ->getRepository('AppBundle:Operation')
            ->find($request->get('id'));

        $em->remove($operation);
        $em->flush();
        return new JsonResponse($serializer->serialize($operation, 'json', ['groups' => ['operation']]));
    }
}