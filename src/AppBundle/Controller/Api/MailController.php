<?php
namespace AppBundle\Controller\Api;

use AppBundle\Entity\Customer;
use AppBundle\Entity\OperationHistory;
use FOS\RestBundle\Controller\Annotations as Rest;
use mysql_xdevapi\Exception;
use OurThread;
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

    public function generateArguments(OperationHistory $history)
    {
        $em = $this->getDoctrine()->getManager();
        $customer = $em->getRepository("AppBundle:Customer")->findOneBy(['name' => $history->getCustomer()]);
        $timeSpent = $history->getBeginningDate()->diff($history->getEndingDate());

        $arrivingDate = $history->getBeginningDate();
        $arrivingDate->setTimezone(new \DateTimezone("Asia/Kuwait"));
        $endingDate = $history->getEndingDate();
        $endingDate->setTimezone(new \DateTimezone("Asia/Kuwait"));

        return [
            "history" => $history,
            "timeSpent" => $timeSpent->h . 'h:' . $timeSpent->i . 'm:' . $timeSpent->s . "s",
            "completed" => $endingDate->format("l jS F Y"),
            "color" => $customer->getColor(),
            "textColor" => $this->getGoodColorOfText($customer->getColor(), "white", "black"),
            "arrivingHour" => $arrivingDate->format('H:i'),
            "customer" => $customer,
            "pdf" => false,
        ];
    }

    private function generatePdfAndSendMail(Request $request, OperationHistory $operationHistory)
    {
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

        $file = "mail.log";
        if (!file_exists($file))
            fopen($file, "w");
        $now = new \DateTime();
        $current = file_get_contents($file);
        $current .= "\n=== SEND MAIL REQUEST BEGIN AT : " . $now->format("Y-m-d H:i:s") . "===\n";

        foreach ($sendTo as $email) {
            $current .= "sending mail to : '" . $email . "'\n";
        }
        file_put_contents($file, $current);

        $timeSpent = $operationHistory->getEndingDate()->diff($operationHistory->getBeginningDate());
        $subject = $operationHistory->getCustomer() . " - " . $operationHistory->getPlace();
//        $attachment = $this->generatorPdf($request, $operationHistory);

        $arrivingDate = $operationHistory->getBeginningDate();
        $arrivingDate->setTimezone(new \DateTimezone("Asia/Kuwait"));
        $endingDate = $operationHistory->getEndingDate();
        $endingDate->setTimezone(new \DateTimezone("Asia/Kuwait"));

        $params = [
            "history" => $operationHistory,
            "timeSpent" => $timeSpent->h . 'h:' . $timeSpent->i . 'm:' . $timeSpent->s . "s",
            "completedDate" => $endingDate->format("l jS F Y"),
            "operationName" => $operationHistory->getCustomer() . ' - ' . $operationHistory->getPlace(),
            "atmName" => $operationHistory->getPlace(),
            "arrivedOnSite" => $arrivingDate->format("H:i"),
            "nbTasks" => $operationHistory->getTasks()->count(),
            "color" => $customer->getColor()
        ];

        $mail = $this->container->get('mail.send');

        $current = file_get_contents($file);
        $current .= "sending mail for OH : '" . $operationHistory->getId() . "'\n";
        file_put_contents($file, $current);


        $in15min = $operationHistory->getLastTimeSent();
        if ($in15min)
            $in15min->modify("+15 min");
        if ($operationHistory->getLastTimeSent() == null || $now->getTimestamp() > $in15min->getTimestamp()) {
            $operationHistory->setLastTimeSent($now);
            $this->getDoctrine()->getManager()->flush();
            $current = file_get_contents($file);
            $current .= "mail sent\n";
            file_put_contents($file, $current);
            $mail->sendMail($sendTo, $subject, $params, "mail/job.html.twig", null);
        } else {
            $current = file_get_contents($file);
            $current .= "mail has not been sent, it was less than 15min from last mail sent\n";
            file_put_contents($file, $current);
        }
    }

    private function generatorPdf(Request $request, OperationHistory $history) {
        $today = new \DateTime();
        $htmlCode = $this->renderView(
            ':home/operationHistory/job-report:job-report.html.twig', $this->generateArguments($history)
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

//        $file = $this->file($fileGenerator->returnFile("/../web/pdf/",  $fileName));
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

//        $mail = $this->container->get('mail.send');
//        try {
//            $this->generatePdfAndSendMail($request, $operationHistory);
//        } catch (Exception $e) {
//            $file = "mail.log";
//            if (!file_exists($file))
//                fopen($file, "w");
//            $current = file_get_contents($file);
//            $current .= "ERROR : " . $e->getMessage() . "\n";
//            file_put_contents($file, $current);
//        }


        return new Response(json_encode(array(
            'success' => 'true'
        )));
    }

    /**
     * @Rest\View(serializerGroups={"operation"})
     * @Rest\Get("/api/mail/backup-send/{id}/")
     *
     * @param Request $request
     * @param OperationHistory $operationHistory
     * @param SerializerInterface $serializer
     * @return JsonResponse|Response
     */
    public function sendBackupMailAction(Request $request, OperationHistory $operationHistory, SerializerInterface $serializer)
    {
        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);

//        $mail = $this->container->get('mail.send');
        try {
            $this->generatePdfAndSendMail($request, $operationHistory);
        } catch (Exception $e) {
            $file = "mail.log";
            if (!file_exists($file))
                fopen($file, "w");
            $current = file_get_contents($file);
            $current .= "ERROR : " . $e->getMessage() . "\n";
            file_put_contents($file, $current);
        }


        return new Response(json_encode(array(
            'success' => 'true'
        )));
    }


}