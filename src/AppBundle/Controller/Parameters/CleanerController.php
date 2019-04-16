<?php

namespace AppBundle\Controller\Parameters;

use AppBundle\Controller\ParametersController;
use AppBundle\Entity\Cleaner;
use AppBundle\Form\CleanerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/parameters")
 *
 * Class CleanerController
 * @package AppBundle\Controller
 */
class CleanerController extends ParametersController
{
    /**
     * @Route("/cleaners", name="parameters_cleaners_page")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $cleaners = $em->getRepository("AppBundle:Cleaner")->findAll();

        $cleaners = ["cleaners" => $cleaners];
        return $this->render('parameters/cleaner/index.html.twig',
            array_merge($cleaners, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/cleaners/create", name="parameters_create_cleaner_page")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createAction(Request $request)
    {
        $cleaner = new Cleaner();
        $form = $this->createForm(CleanerType::class, $cleaner);
        $form->get('user')->setData($this->getDoctrine()->getRepository('AppBundle:User')->getUsersNotCleaners());

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            var_dump($request->request);
            //die();
            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($cleaner);
            $em->flush();

            return $this->redirect($this->generateUrl('parameters_cleaners_page'));
        }

        $params = [
            'form' => $form->createView()
        ];

        return $this->render('parameters/cleaner/create.html.twig', array_merge($params, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/cleaner/{id}/delete", name="parameters_delete_cleaner_page")
     *
     * @param Request $request
     * @param Cleaner $cleaner
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteAction(Request $request, Cleaner $cleaner)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $em->remove($cleaner);
        $em->flush();
        return $this->redirect($this->generateUrl('parameters_cleaners_page'));
    }
}
