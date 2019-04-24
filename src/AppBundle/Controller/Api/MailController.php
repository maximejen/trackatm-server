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

    private function getGoodColorOfText($bgColor, $lightColor, $darkColor)
    {
        $color = ($bgColor[0] == '#') ? substr($bgColor, 1, 7) : $bgColor;
        $r = intval(substr($color, 0, 2), 16);
        $g = intval(substr($color, 2, 2), 16);
        $b = intval(substr($color, 4, 2), 16);
        return ((($r * 0.299) + ($g * 0.587) + ($b * 0.114)) > 186) ? $darkColor : $lightColor;
    }

    private function generatorPdf(Request $request, OperationHistory $history) {
        $em = $this->getDoctrine()->getManager();
        $customer = $em->getRepository("AppBundle:Customer")->findOneBy(['name' => $history->getCustomer()]);
        $timeSpent = $history->getEndingDate()->diff($history->getBeginningDate());
        $today = new \DateTime();
        $fileGenerator = $this->container->get('file_genertor');
        $htmlCode = $this->renderView(
            ':home/operationHistory/job-report:job-report.html.twig',
            [
                "history" => $history,
                "timeSpent" => $timeSpent->h . 'h:' . $timeSpent->m . 'm',
                "completed" => $history->getEndingDate()->format("l jS F Y"),
                "color" => $customer->getColor(),
                "textColor" => $this->getGoodColorOfText($customer->getColor(), "white", "black"),
                "arrivingHour" => $history->getBeginningDate()->format('H:i'),
                "customer" => $customer,
                "pdf" => true,
            ]
        );

        $post = json_encode([
            "htmlCode" => $htmlCode,
            "type" => "htmlToPdf"
        ]);

        $header = [
            'Content-Type: application/json',
            "Content-Length: " . strlen($post)
        ];

        $ch = curl_init("https://api.sejda.com/v1/tasks");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $fileName = $today->getTimestamp() . '-generated.pdf';
        file_put_contents(
            $request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/pdf/' . $fileName,
            curl_exec($ch)
        );
        curl_close($ch);

        $file = $this->file($fileGenerator->returnFile("/../web/pdf/",  $fileName));
        return $request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/pdf/' . $fileName;
    }

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
        $attachment = $this->generatorPdf($request, $operationHistory);
        $params = [
            "history" => $operationHistory,
            "timeSpent" => $timeSpent->h . 'h:' . $timeSpent->m . 'm',
            "completedDate" => $operationHistory->getEndingDate()->format("l jS F Y"),
            "operationName" => $operationHistory->getCustomer() . ' - ' . $operationHistory->getPlace(),
            "atmName" => $operationHistory->getPlace(),
            "arrivedOnSite" => $operationHistory->getBeginningDate()->format("H:i"),
            "nbTasks" => $operationHistory->getTasks()->count(),
            "color" => $customer->getColor()
        ];

        $mail = $this->container->get('mail.send');
        $mail->sendMail($sendTo, $subject, $params, "mail/job.html.twig", $attachment);

        return new Response(json_encode(array(
            'success' => 'true'
        )));
    }


}