<?php

namespace AppBundle\Controller\Parameters;

use AppBundle\Controller\ParametersController;
use AppBundle\Entity\Customer;
use AppBundle\Form\CustomerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/parameters")
 *
 * Class CustomerController
 * @package AppBundle\Controller
 */
class CustomerController extends ParametersController
{
    /**
     * @Route("/customers", name="parameters_customers_page")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $customers = $em->getRepository("AppBundle:Customer")->findAll();

        $customers = ["customers" => $customers];
        return $this->render('parameters/customer/index.html.twig',
            array_merge($customers, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/customers/create", name="parameters_create_customer_page")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createAction(Request $request)
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $em = $this->get('doctrine.orm.entity_manager');

            $em->persist($customer);
            $em->flush();

            return $this->redirect($this->generateUrl('parameters_customers_page'));
        }

        $params = [
            'form' => $form->createView()
        ];

        return $this->render('parameters/customer/create.html.twig', array_merge($params, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/customers/{id}/edit", name="parameters_edit_customer_page")
     *
     * @param Request $request
     * @param Customer $customer
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function editAction(Request $request, Customer $customer)
    {
        $form = $this->createForm(CustomerType::class, $customer);

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $em = $this->get('doctrine.orm.entity_manager');

            $em->persist($customer);
            $em->flush();

            return $this->redirect($this->generateUrl('parameters_customers_page'));
        }

        $params = [
            'form' => $form->createView()
        ];

        return $this->render('parameters/customer/edit.html.twig', array_merge($params, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/customer/{id}/delete", name="parameters_delete_customer_page")
     *
     * @param Request $request
     * @param Customer $customer
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteAction(Request $request, Customer $customer)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $em->remove($customer);
        $em->flush();
        return $this->redirect($this->generateUrl('parameters_customers_page'));
    }
}
