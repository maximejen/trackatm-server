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
        $period = new DatePeriod(
            $date1,
            new DateInterval('P1D'),
            $date2->modify("+ 1 days")
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

    private function hasBeenDoneLastSevenDays($histories, $planning)
    {
        $nbTimesToBeDone = [];
        $nbTimesDone = [];
        foreach ($planning as $date) {
            /** @var Operation $operation */
            foreach ($date as $operation) {
                if (!array_key_exists($operation->getPlace()->getName(), $nbTimesToBeDone))
                    $nbTimesToBeDone[$operation->getPlace()->getName()] = 0;
                $nbTimesToBeDone[$operation->getPlace()->getName()]++;
            }
        }
        /** @var OperationHistory $history */
        foreach ($histories as $history) {
            if (!array_key_exists($history->getPlace(), $nbTimesDone))
                $nbTimesDone[$history->getPlace()] = 0;
            $nbTimesDone[$history->getPlace()]++;
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

        $today = new\DateTime();
        $weekAgo = new \DateTime();
        $weekAgo->modify('-6 days');

        $em = $this->getDoctrine()->getManager();
        $cleaner = $em->getRepository('AppBundle:Cleaner')->findOneBy(['user' => $user]);
        $operations = $em->getRepository('AppBundle:Operation')->findBy(['cleaner' => $cleaner]);
        $histories = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesByCleanerAndBetweenTwoDates($cleaner, $weekAgo, $today);

        $week = $this->getWeek($operations);
        $planning = $this->getOperationsPlanning($weekAgo, $today, $week);
//        $this->hasBeenDoneLastSevenDays($histories, $planning);
        $operations = $this->fromPlanningToFlat($planning);
//        foreach ($operations as $operation)
//            var_dump($operation->getDay() . " / " . $operation->getPlace() . " : " . $operation->isDone());
//            $operation->setDone(false);
        return new Response($serializer->serialize($operations, 'json', ['groups' => ['operation']]));
    }

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