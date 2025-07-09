<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class HomeController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->getToken() || $user->getToken() == "") {
            $user->setToken(md5(random_int(1, 1000000)) . md5(random_int(1, 10000000)));
            $this->getDoctrine()->getManager()->flush();
        }

        return $this->redirect($this->generateUrl('planningpage'));
    }

    /**
     * @Route("/support", name="supportpage", methods={"GET"})
     */
    public function supportAction()
    {
        return $this->render('home/support/index.html.twig');
    }

    /**
     * @Route("/privacy-policy", name="privacy-policy", methods={"GET"})
     */
    public function privacyPolicyAction()
    {
        return $this->render('home/privacy-policy/index.html.twig');
    }

    /**
     * @Route("/terms-of-service", name="terms-of-service", methods={"GET"})
     */
    public function termsAction()
    {
        return $this->render('home/terms-of-service/index.html.twig');
    }

    /**
     * @Route("/privacy", name="privacy")
     */
    public function privacyAction()
    {
        return $this->render(':home/operationHistory:privacy.html.twig');
    }

    protected function getMenuParameters()
    {
        return
            [
                0 => [
                    "text" => "Planning",
                    "path" => "planningpage"
                ],
                1 => [
                    "text" => "OperationsGroups",
                    "path" => "operation_groups"
                ],
                2 => [
                    "text" => "Operation History",
                    "path" => "operationhistorypage"
                ],
            ];
    }
}
