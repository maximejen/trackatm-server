<?php
namespace AppBundle\Controller\Api;

use AppBundle\Entity\Cleaner;
use AppBundle\Entity\CleanerPlanningDay;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class CleanerController
 * @package AppBundle\Controller\Api
 */
class CleanerController extends Controller
{
    /**
     * @Rest\View(serializerGroups={"cleaner"})
     * @Rest\Get("/api/cleaners")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     *
     * @return Response
     */
    public function getCleanersAction(Request $request, SerializerInterface $serializer)
    {
        $cleaners = $this->get('doctrine.orm.entity_manager')
            ->getRepository('AppBundle:Cleaner')
            ->findAll();

        return new Response($serializer->serialize($cleaners, 'json', ['groups' => ['cleaner']]));
    }

    /**
     * @Rest\View(serializerGroups={"cleaner"})
     * @Rest\Get("/api/cleaner/{id}")
     *
     * @param Request $request
     * @param Cleaner $cleaner
     * @param SerializerInterface $serializer
     * @return Response
     */
    public function getCleanerAction(Request $request, Cleaner $cleaner, SerializerInterface $serializer)
    {
        return new Response($serializer->serialize($cleaner, 'json', ['groups' => ['cleaner']]));
    }

    /**
     * @Rest\View(serializerGroups={"cleaner"})
     * @Rest\Get("/api/cleaner/{id}/days")
     *
     * @param Request $request
     * @param Cleaner $cleaner
     * @param SerializerInterface $serializer
     *
     * @return Response
     */
    public function getCleanerPlanningDaysAction(Request $request, Cleaner $cleaner, SerializerInterface $serializer)
    {
        return new Response($serializer->serialize($cleaner->getPlanning(), 'json', ['groups' => ['day']]));
    }

    /**
     * @Rest\View(serializerGroups={"cleaner"})
     * @Rest\Get("/api/cleaner/{id}/day/{day}")
     *
     * @param Request $request
     * @param Cleaner $cleaner
     * @param SerializerInterface $serializer
     *
     * @return Response
     */
    public function getCleanerPlanningDayAction(Request $request, Cleaner $cleaner, SerializerInterface $serializer)
    {
        $reqDay = $request->request->get('day');
        $planning = $cleaner->getPlanning();
        $requestedDay = null;

        /** @var CleanerPlanningDay $day */
        foreach ($planning as $day) {
            if ($day->getDay() == $reqDay)
                $requestedDay = $day;
        }

        return new Response($serializer->serialize($requestedDay, 'json', ['groups' => ['day']]));
    }

    /**
     * @Rest\View(serializerGroups={"cleaner"})
     * @Rest\Get("/api/cleaner/{id}/today")
     *
     * @param Request $request
     * @param Cleaner $cleaner
     * @param SerializerInterface $serializer
     *
     * @return Response
     * @throws \Exception
     */
    public function getCleanerPlanningTodayAction(Request $request, Cleaner $cleaner, SerializerInterface $serializer)
    {
        $date = new \DateTime();
        $planning = $cleaner->getPlanning();
        $requestedDay = null;

        /** @var CleanerPlanningDay $day */
        foreach ($planning as $day) {
            if ($day->getDay() == $date->format('D'))
                $requestedDay = $day;
        }

        return new Response($serializer->serialize($requestedDay, 'json', ['groups' => ['day']]));
    }
}