<?php

namespace AppBundle\Controller\Parameters;

use AppBundle\Controller\ParametersController;
use AppBundle\Entity\Place;
use AppBundle\Form\PlaceBulkCreateType;
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
        } else {
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

            if (isset($_POST['create_and_redirect'])) {
                return $this->redirect($this->generateUrl('operation_create', ["search" => $place->getName()]));
            }
            return $this->redirect($this->generateUrl('parameters_places_page'));
        }

        $params = [
            'form' => $form->createView()
        ];

        return $this->render('parameters/place/create.html.twig', array_merge($params, $this->getCommonParametersForParameters()));
    }

    /**
     * @Route("/places/bulk_create", name="parameters_bulk_create_place_page")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bulkCreateAction(Request $request)
    {
        $form = $this->createForm(PlaceBulkCreateType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->get('csv')->getData();
            $fileGenerator = $this->container->get('file_genertor');
            $fileGenerator->fromCSVToPlaces($this->getDoctrine()->getManager(), $data);
        }

        // TODO : make a text input to put the csv content inside.
        // TODO : read the CSV and then generate all the places based on the CSV.

         // TODO : Later implement a field to ask the customer for all the given places. In that case, ignore a column named customer in the csv.

        $params = ['form' => $form->createView()];
        return $this->render('parameters/place/bulk_create.html.twig', array_merge($params, $this->getCommonParametersForParameters()));
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
