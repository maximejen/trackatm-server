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
        $lastWeekStart = clone $weekStart;
        $lastWeekEnd = clone $weekStart;
        $lastWeekStart->modify("last sunday");
        $lastWeekEnd->modify("last saturday");

        // get limits of the week before the last one
        $lastLastWeekStart = clone $lastWeekStart;
        $lastLastWeekEnd = clone $lastWeekStart;
        $lastLastWeekStart->modify("last sunday");
        $lastLastWeekEnd->modify("last saturday");

        $em = $this->getDoctrine()->getManager();
        $cleaner = $em->getRepository('AppBundle:Cleaner')->findByUser($user);


        // get all the operation_history of this month
        $operationsThisMonth = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesByCleanerThisMonth($cleaner);
        $operations = $em->getRepository('AppBundle:Operation')->findOperationsByCleaner($cleaner);
        // Filter all the operations that were already done the number asked when they were created.
        $operations = array_filter($operations, function (Operation $element) use ($operationsThisMonth) {
            if ((is_int($element->getNumberMaxPerMonth()) && count($operationsThisMonth[$element->getPlace()->getName() . $element->getCleaner()->__toString()]) >= $element->getNumberMaxPerMonth()))
                return (false);
            return (true);
        });
        $historiesActualWeek = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesByCleanerAndBetweenTwoDates($cleaner, $weekStart, $weekEnd);


        $historiesNextWeek = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesByCleanerAndBetweenTwoDates($cleaner, $nextWeekStart, $nextWeekEnd);
        $historiesLastWeek = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesByCleanerAndBetweenTwoDates($cleaner, $lastWeekStart, $lastWeekEnd);
        $historiesLastLastWeek = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesByCleanerAndBetweenTwoDates($cleaner, $lastLastWeekStart, $lastLastWeekEnd);

        $week = $this->getWeek($operations);

        $flat = $request->query->get('flat') == null ? "true" : $request->query->get('flat');

        $planning = $this->getOperationsPlanning($weekStart, $weekEnd, $week);

        // duplicate week so that there is no edit of the Operations of the first week
        $week1 = [];
        foreach ($week as $day => $elements) {
            !array_key_exists($day, $week1) && $week1[$day] = [];
            foreach ($elements as $element) $week1[$day][] = clone $element;
        }
        $nextPlanning = $this->getOperationsPlanning($nextWeekStart, $nextWeekEnd, $week1);

        $lastWeek = $this->getWeek($operations);
        $lastWeekPlanning = $this->getOperationsPlanning($lastWeekStart, $lastWeekEnd, $lastWeek);
        $lastLastWeek = $this->getWeek($operations);
        $lastLastWeekPlanning = $this->getOperationsPlanning($lastLastWeekStart, $lastLastWeekEnd, $lastLastWeek);
        // 1.0.8

        if ($flat == "true") {
            foreach ($operations as $operation) $operation->setDone(false); // all operations are on false when it's flat for compatibility reasons.
            $operations = $this->fromPlanningToFlat($planning);
            return new Response($serializer->serialize($operations, 'json', ['groups' => ['operation']]));
        } else {
            $this->determineOperationsDone($historiesLastLastWeek, $lastLastWeekPlanning);
            $this->determineOperationsDone($historiesLastWeek, $lastWeekPlanning);
            $this->determineOperationsDone($historiesActualWeek, $planning);
            $this->determineOperationsDone($historiesNextWeek, $nextPlanning);
            $planning = array_merge($planning, $nextPlanning, $lastWeekPlanning, $lastLastWeekPlanning);

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