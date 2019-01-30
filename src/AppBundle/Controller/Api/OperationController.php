<?php
namespace AppBundle\Controller\Api;

use AppBundle\Entity\Operation;
use AppBundle\Form\OperationType;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class OperationController
 * @package AppBundle\Controller\Api
 */
class OperationController extends Controller
{
    /**
     * @Rest\View(serializerGroups={"operation"})
     * @Rest\Get("/operations/")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return Response
     */
    public function getOperationsAction(Request $request, SerializerInterface $serializer)
    {
        $operations = $this->get('doctrine.orm.entity_manager')
            ->getRepository('AppBundle:Operation')
            ->findAll();

        return new Response($serializer->serialize($operations, 'json', ['groups' => ['operation']]));
    }

    /**
     * @Rest\View(serializerGroups={"operation"})
     * @Rest\Get("/operation/{id}")
     *
     * @param Request $request
     * @param Operation $operation
     * @param SerializerInterface $serializer
     * @return Response
     */
    public function getOperationAction(Request $request, Operation $operation, SerializerInterface $serializer)
    {
        return new Response($serializer->serialize($operation, 'json', ['groups' => ['operation']]));
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_CREATED)
     * @Rest\Post("/operations")
     *
     * @param Request $request
     *
     * @return Operation|\Symfony\Component\Form\FormInterface
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postOperationsAction(Request $request)
    {
        $operation = new Operation();
        $form = $this->createForm(OperationType::class, $operation);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $em = $this->get('doctrine.orm.entity_manager');

            $em->persist($operation);
            $em->flush();

            return $operation;
        } else {
            return $form;
        }
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_NO_CONTENT)
     * @Rest\Delete("/operations/{id}")
     *
     * @param Request $request
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeOperationAction(Request $request)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        /* @var $operation Operation */
        $operation = $em
            ->getRepository('AppBundle:Operation')
            ->find($request->get('id'));

        $em->remove($operation);
        $em->flush();
    }
}