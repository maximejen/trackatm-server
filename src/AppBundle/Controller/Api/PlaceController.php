<?php
namespace AppBundle\Controller\Api;

use AppBundle\Entity\Place;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class PlaceController
 * @package AppBundle\Controller\Api
 */
class PlaceController extends ApiController
{
    /**
     * @Rest\View(serializerGroups={"place"})
     * @Rest\Get("/api/places/")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return JsonResponse|Response
     */
    public function getPlacesAction(Request $request, SerializerInterface $serializer)
    {
        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);
        $places = $this->get('doctrine.orm.entity_manager')
            ->getRepository('AppBundle:Place')
            ->findAll();

        return new Response($serializer->serialize($places, 'json', ['groups' => ['place']]));
    }

    /**
     * @Rest\View(serializerGroups={"place"})
     * @Rest\Get("/api/place/{id}")
     *
     * @param Request $request
     * @param Place $place
     * @param SerializerInterface $serializer
     * @return Response
     */
    public function getPlaceAction(Request $request, Place $place, SerializerInterface $serializer)
    {
        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);
        return new Response($serializer->serialize($place, 'json', ['groups' => ['place']]));
    }
}