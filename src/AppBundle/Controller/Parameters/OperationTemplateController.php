<?php

namespace AppBundle\Controller\Parameters;

use AppBundle\Controller\ParametersController;
use AppBundle\Entity\OperationTaskTemplate;
use AppBundle\Entity\OperationTemplate;
use AppBundle\Form\OperationTemplateType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/parameters")
 *
 * Class OperationTemplateController
 * @package AppBundle\Controller
 */
class OperationTemplateController extends ParametersController
{
    /**
     * @Route("/operationTemplates", name="parameters_operationTemplates_page")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $search = $request->get("search");
        if ($search != NULL && $search != "") {
            $operationTemplates = $em->getRepository("AppBundle:OperationTemplate")->findOperationTemplateByName($search);
        }
        else {
            $operationTemplates = $em->getRepository("AppBundle:OperationTemplate")->findAll();
        }

        $params = ["operationTemplates" => $operationTemplates, 'search' => $search];
        return $this->render('parameters/operationTemplate/index.html.twig',
            array_merge($params, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/operationTemplates/create", name="parameters_create_operationTemplate_page")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createAction(Request $request)
    {
        $operationTemplate = new OperationTemplate();
        $form = $this->createForm(OperationTemplateType::class, $operationTemplate);

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            /** @var OperationTaskTemplate $elem */
            foreach ($operationTemplate->getTasks() as $elem) {
                $elem->setOperation($operationTemplate);
            }
            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($operationTemplate);
            $em->flush();

            return $this->redirect($this->generateUrl('parameters_operationTemplates_page'));
        }

        $params = [
            'form' => $form->createView()
        ];

        return $this->render('parameters/operationTemplate/create.html.twig', array_merge($params, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/operationTemplates/{id}/edit", name="parameters_edit_operationTemplate_page")
     *
     * @param Request $request
     * @param OperationTemplate $operationTemplate
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function editAction(Request $request, OperationTemplate $operationTemplate)
    {
        $form = $this->createForm(OperationTemplateType::class, $operationTemplate);

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $em = $this->get('doctrine.orm.entity_manager');

            $em->persist($operationTemplate);
            $em->flush();

            return $this->redirect($this->generateUrl('parameters_operationTemplates_page'));
        }

        $params = [
            'form' => $form->createView()
        ];

        return $this->render('parameters/operationTemplate/edit.html.twig', array_merge($params, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/operationTemplate/{id}/delete", name="parameters_delete_operationTemplate_page")
     *
     * @param Request $request
     * @param OperationTemplate $operationTemplate
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteAction(Request $request, OperationTemplate $operationTemplate)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $em->remove($operationTemplate);
        $em->flush();
        return $this->redirect($this->generateUrl('parameters_operationTemplates_page'));
    }
}
