<?php
namespace AppBundle\Controller\Api;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;

/**
 * Class UserController
 * @package AppBundle\Controller\Api
 */
class UserController extends Controller
{
    /**
     * @Rest\Route("/users/")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsersAction(Request $request)
    {
        $users = $this->get('doctrine.orm.entity_manager')
            ->getRepository('AppBundle:User')
            ->findAll();

        $formatted = [];
        foreach ($users as $user) {
            $formatted[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail()
            ];
        }

        return new JsonResponse($formatted);
    }

    /**
     * @Rest\Route("/user/{id}")
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function getUserAction(Request $request, User $user)
    {
        $formatted = [
            'id' => $user->getId(),
            'email' => $user->getEmail()
        ];

        return new JsonResponse($formatted);
    }
}