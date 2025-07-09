<?php

namespace AppBundle\Controller\Api;

use AppBundle\Entity\OperationHistory;
use AppBundle\Entity\Place;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class CustomController
 * @package AppBundle\Controller\Api
 */
class CustomController extends ApiController
{
    /**
     * @Rest\Post  ("/api/find-places")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return JsonResponse|Response
     */
    public function postFindPlacesAction(Request $request, SerializerInterface $serializer)
    {
//        if (!$this->checkUserIsConnected($request))
//            return new JsonResponse(['message' => "you need to be connected"], 403);

        $params = array();
        $content = $request->getContent(); //  $this->get("request")->getContent();
        if (!empty($content)) {
            $params = json_decode($content, true); // 2nd param to get as array
        }

        $names = array_map(function ($entry) {
            return $entry[0];
        }, $params);

        $places = array();;
        $em = $this->get('doctrine.orm.entity_manager');
        $PR = $em->getRepository('AppBundle:Place');
        foreach ($names as $key => $name) {
            $place = $PR->findPlaceByName($name)[0];
            if ($place == null) {
                var_dump("could not find a place with this name " . $name);
            }
            $places[] = $place;
        }

        $ids = array();
        $indexUsed = array();

        try {
            /** @var Place $place */
            foreach ($places as $place) {

                $index = -1;
                foreach ($params as $idx => $param) {
                    if ($place != null && $param[0] == $place->getIdentifier()) {
                        $index = $idx;
                    }
                }

                if ($index != -1 && !$indexUsed[$index]) {
                    $indexUsed[$index] = $index;
                }
                if ($index != -1 && $place != null) {

                    $histories = $em
                        ->getRepository('AppBundle:OperationHistory')
                        ->findOperationHistoriesByPlaceNameBetweenTwoDates($place->getName(), "2024-11-01", "2024-12-31");

                    // replace the name with the new one.
                    $place->setName($params[$index][1] . $place->getNameWithoutIdentifier());

                    /** @var OperationHistory $history */
                    foreach ($histories as $history) {
                        // replace the name of the place in the history.
                        $history->setPlace($place->getName());
                    }

                    $ids[$place->getId()] = [$place->getIdentifier(), $params[$index][1], $place->getName(), $params[$index][1] . $place->getNameWithoutIdentifier(), count($histories)];

                } else {
                    if ($place != null)
                        $ids[] = ["not found", $place->getIdentifier(), $place->getName()];
                }
            }
            $em->flush();
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

        // Create the JSON response with just the IDs
        $response = new Response(json_encode([
            'success' => 'true',
            'ids' => $ids
        ]));

        // Set the response headers for JSON content type
        $response->headers->set('Content-Type', 'application/json');
        $response->setStatusCode(200);
        return $response;
    }

    /**
     * @Rest\Post ("/api/replace-names")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return JsonResponse|Response
     */
    public function postReplaceNamesAction(Request $request, SerializerInterface $serializer)
    {
//        if (!$this->checkUserIsConnected($request))
//            return new JsonResponse(['message' => "you need to be connected"], 403);
//        $places = $this->get('doctrine.orm.entity_manager')
//            ->getRepository('AppBundle:Place')
//            ->findAll();

        $params = array();
        $content = $request->getContent(); //  $this->get("request")->getContent();
        if (!empty($content)) {
            $params = json_decode($content, true); // 2nd param to get as array
        }

        $names = array_map(function ($entry) {
            return $entry->id;
        }, $params);

        $places = $this->get('doctrine.orm.entity_manager')
            ->getRepository('AppBundle:Place')
            ->findPlaceByNames($names);

        // Extract the IDs from the $places array
        $ids = array_map(function ($place) {
            return [$place->getId() => ["id" => $place->getIdentifier(), "name" => $place->getNameWithoutIdentifier(), "oldName" => $place->getName(), "newName" => ""]];
        }, $places);

        // Create the JSON response with just the IDs
        $response = new Response(json_encode([
            'success' => 'true',
            'ids' => $ids
        ]));

        // Set the response headers for JSON content type
        $response->headers->set('Content-Type', 'application/json');
        $response->setStatusCode(200);
        return $response;
    }

}
