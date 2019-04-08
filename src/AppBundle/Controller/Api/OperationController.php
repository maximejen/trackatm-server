<?php
namespace AppBundle\Controller\Api;

use AppBundle\Entity\Operation;
use AppBundle\Form\OperationType;
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