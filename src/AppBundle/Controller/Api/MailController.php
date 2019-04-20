<?php
namespace AppBundle\Controller\Api;

use AppBundle\Entity\Cleaner;
use AppBundle\Entity\Customer;
use AppBundle\Entity\Image;
use AppBundle\Entity\Operation;
use AppBundle\Entity\OperationHistory;
use AppBundle\Entity\OperationTaskHistory;
use AppBundle\Form\OperationType;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class MailController
 * @package AppBundle\Controller\Api
 */
class MailController extends ApiController
{


    /**
     * @Rest\View(serializerGroups={"operation"})
     * @Rest\Get("/api/mail/send/{id}/")
     *
     * @param Request $request
     * @param OperationHistory $operationHistory
     * @param SerializerInterface $serializer
     * @return JsonResponse|Response
     */
    public function getMailAction(Request $request, OperationHistory $operationHistory, SerializerInterface $serializer)
    {
        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);
        $sendTo = [];
        $entityManager =  $this->get('doctrine.orm.entity_manager');
        /** @var Customer $customer */
        $customer = $entityManager
            ->getRepository('AppBundle:Customer')
            ->findOneBy(['name' => $operationHistory->getCustomer()]);
        array_push($sendTo, $customer->getEmail());

        $admin = $entityManager
            ->getRepository('AppBundle:User')
            ->findBy(['admin' => true]);
        foreach ($admin as $item) {
            array_push($sendTo, $item->getEmail());
        }
        $timeSpent = $operationHistory->getEndingDate()->diff($operationHistory->getBeginningDate());
        $subject = $operationHistory->getPlace();
        $params = [
            "history" => $operationHistory,
            "timeSpent" => $timeSpent->h . 'h:' . $timeSpent->m . 'm',
            "completed" => $operationHistory->getEndingDate()->format("l jS F Y"),
            "color" => $customer->getColor()
        ];

        $mail = $this->container->get('mail.send');
        $mail->sendMail($sendTo, $subject, $params, "mail/job.html.twig");

        return new Response(json_encode(array(
            'success' => 'true'
        )));
    }


}