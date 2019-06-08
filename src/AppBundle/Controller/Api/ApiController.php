<?php
namespace AppBundle\Controller\Api;

use AppBundle\AppBundle;
use AppBundle\Entity\Cleaner;
use AppBundle\Entity\CleanerPlanningDay;
use AppBundle\Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class ApiController
 * @package AppBundle\Controller\Api
 */
class ApiController extends Controller
{
    /**
     * @Rest\Get("/api/token")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getTokenAction(Request $request)
    {
        $entityManager =  $this->get('doctrine.orm.entity_manager');

        $user = $entityManager
            ->getRepository('AppBundle:User')
            ->findOneBy(["token" => $request->query->get('token')]);
        if (!$user) {
            $response = new Response();
            $response->setStatusCode('400');
            $response->setContent(json_encode(array(
                'success' => false,
                'message' => 'Account not found')));
            return $response;
        }
        return new Response(json_encode(array(
            'success' => true,
            'cleanerid' => $this->getCleanerId($user))));
    }

    /**
     * @Rest\Post("/api/login")
     *
     * @param  Request $request
     *
     * @return Response
     * @throws \Exception
     */
    public function postLoginAction(Request $request)
    {
        $params = array();
        $content = $request->getContent(); //  $this->get("request")->getContent();
        if (!empty($content))
        {
            $params = json_decode($content, true); // 2nd param to get as array
        }

        $entityManager =  $this->get('doctrine.orm.entity_manager');
        $user = $entityManager
            ->getRepository('AppBundle:User')
            ->findOneBy(["username" => $params['username']]);
        if (!$user || !array_key_exists('username', $params) || !array_key_exists('password', $params)) {
            $response = new Response();
            $response->setStatusCode('400');
            $response->setContent(json_encode(array(
                'success' => false,
                'message' => 'Account not found')));
            return $response;
        }

        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);

        $bool = $encoder->isPasswordValid($user->getPassword(),$params['password'],$user->getSalt());

        if (!$bool) {
            return new Response(json_encode(array(
                'success' => false,
                'message' => "Bad password")));
        }
        if ($user->getToken() == "") {
            $user->setToken(md5(random_int(1, 1000000)) . md5(random_int(1, 10000000)));
            $entityManager->flush();
        }


        return new Response(json_encode(array(
            'success' => 'true',
            'token' => $user->getToken(),
            'cleanerid' => $this->getCleanerId($user))));

    }

    /**
     * @Rest\Get("/api/logout")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \Exception
     */
    public function getLogoutAction(Request $request)
    {
        $entityManager =  $this->get('doctrine.orm.entity_manager');

        $user = $entityManager
            ->getRepository('AppBundle:User')
            ->findOneBy(["token" => $request->query->get('token')]);
        if (!$user) {
            $response = new Response();
            $response->setStatusCode('400');
            $response->setContent(json_encode(array(
                'success' => false,
                'message' => 'Account not found')));
            return $response;
        }
        $user->setToken("");
        $entityManager->flush();
        return new Response(json_encode(array(
            'success' => true)));
    }


    /**
     * @param User $user
     * @return int
     */
    protected function getCleanerId(User $user)
    {
        $entityManager =  $this->get('doctrine.orm.entity_manager');
        $cleaner = $entityManager
            ->getRepository('AppBundle:Cleaner')
            ->findOneBy(["user" => $user]);
        if (!$cleaner)
            return (-1);
        return $cleaner->getId();
    }

    /**
     * @param Request $request
     * @return \AppBundle\Entity\User[]|array|bool|object[]
     */
    protected function checkUserIsConnected(Request $request)
    {
        $token = $request->headers->get('token');
        if (!$token || $token == null)
            return false;
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository("AppBundle:User")->findOneBy(['token' => $token]);
        if (!$user || $user == null)
            return false;
        return $user;
    }

    /**
     * @Rest\Get("/api/upload-app-version")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \Exception
     */
    public function uploadAppVersion(Request $request) {
        /** @var User $user */
        $user = $this->checkUserIsConnected($request);
        if (!$user)
            return new JsonResponse(['message' => "you need to be connected"], 403);
        $version = $request->query->get("version");
        $user->setAppVersion($version);
        $this->getDoctrine()->getManager()->flush();
        return new JsonResponse(['message' => "ok"], 200);
    }
}