<?php
namespace AppBundle\Controller\Api;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class UserController
 * @package AppBundle\Controller\Api
 */
class UserController extends ApiController
{
    /**
     * @Rest\View(serializerGroups={"user"})
     * @Rest\Get("/api/users")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return JsonResponse|Response
     */
    public function getUsersAction(Request $request, SerializerInterface $serializer)
    {
        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);
        $users = $this->get('doctrine.orm.entity_manager')
            ->getRepository('AppBundle:User')
            ->findAll();

        return new Response($serializer->serialize($users, 'json', ['groups' => ['user']]));
    }

    /**
     * @Rest\View(serializerGroups={"user"})
     * @Rest\Get("/api/user/{id}")
     *
     * @param Request $request
     * @param User $user
     * @param SerializerInterface $serializer
     * @return Response
     */
    public function getUserAction(Request $request, User $user, SerializerInterface $serializer)
    {
        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);
        return new Response($serializer->serialize($user, 'json', ['groups' => ['user']]));
    }
}