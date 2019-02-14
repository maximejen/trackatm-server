<?php

namespace AppBundle\Controller\Parameters;

use AppBundle\Controller\ParametersController;
use AppBundle\Entity\Place;
use AppBundle\Form\PlaceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/parameters")
 *
 * Class PlaceController
 * @package AppBundle\Controller
 */
class PlaceController extends ParametersController
{
    /**
     * @Route("/places", name="parameters_places_page")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $search = $request->get("search");
        if ($search != NULL && $search != "") {
            $places = $em->getRepository("AppBundle:Place")->findPlaceByName($search);
        }
        else {
            $places = $em->getRepository("AppBundle:Place")->findAll();
        }

        $params = ["places" => $places, 'search' => $search];
        return $this->render('parameters/place/index.html.twig',
            array_merge($params, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/places/create", name="parameters_create_place_page")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createAction(Request $request)
    {
        $place = new Place();
        $form = $this->createForm(PlaceType::class, $place);

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $em = $this->get('doctrine.orm.entity_manager');

            $em->persist($place);
            $em->flush();

            return $this->redirect($this->generateUrl('parameters_places_page'));
        }

        $params = [
            'form' => $form->createView()
        ];

        return $this->render('parameters/place/create.html.twig', array_merge($params, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/places/{id}/edit", name="parameters_edit_place_page")
     *
     * @param Request $request
     * @param Place $place
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function editAction(Request $request, Place $place)
    {
        $form = $this->createForm(PlaceType::class, $place);

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $em = $this->get('doctrine.orm.entity_manager');

            $em->persist($place);
            $em->flush();

            return $this->redirect($this->generateUrl('parameters_places_page'));
        }

        $params = [
            'form' => $form->createView()
        ];

        return $this->render('parameters/place/edit.html.twig', array_merge($params, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/place/{id}/delete", name="parameters_delete_place_page")
     *
     * @param Request $request
     * @param Place $place
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteAction(Request $request, Place $place)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $em->remove($place);
        $em->flush();
        return $this->redirect($this->generateUrl('parameters_places_page'));
    }
}
