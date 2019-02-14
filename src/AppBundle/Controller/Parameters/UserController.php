<?php

namespace AppBundle\Controller\Parameters;

use AppBundle\Controller\ParametersController;
use AppBundle\Entity\User;
use AppBundle\Form\UserType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/parameters")
 *
 * Class UserController
 * @package AppBundle\Controller
 */
class UserController extends ParametersController
{
    /**
     * @Route("/users", name="parameters_users_page")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $users = $em->getRepository("AppBundle:User")->findAll();

        $users = ["users" => $users];
        return $this->render('parameters/user/index.html.twig',
            array_merge($users, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/users/create", name="parameters_create_user_page")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $em = $this->get('doctrine.orm.entity_manager');

            $user->setEnabled(true);

            $em->persist($user);
            $em->flush();

            return $this->redirect($this->generateUrl('parameters_users_page'));
        }

        $params = [
            'form' => $form->createView()
        ];

        return $this->render('parameters/user/create.html.twig', array_merge($params, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/users/{id}/edit", name="parameters_edit_user_page")
     *
     * @param Request $request
     * @param User $user
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function editAction(Request $request, User $user)
    {
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $em = $this->get('doctrine.orm.entity_manager');

            $em->persist($user);
            $em->flush();

            return $this->redirect($this->generateUrl('parameters_users_page'));
        }

        $params = [
            'form' => $form->createView()
        ];

        return $this->render('parameters/user/edit.html.twig', array_merge($params, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/user/{id}/delete", name="parameters_delete_user_page")
     *
     * @param Request $request
     * @param User $user
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteAction(Request $request, User $user)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $em->remove($user);
        $em->flush();
        return $this->redirect($this->generateUrl('parameters_users_page'));
    }
}
