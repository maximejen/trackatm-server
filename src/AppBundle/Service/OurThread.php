<?php

use AppBundle\Entity\OperationHistory;
use Symfony\Component\HttpFoundation\Request;

class OurThread extends \Thread
{
    private $operationHistory;
    private $request;
    private $mail;

    public function __construct(Request $request, OperationHistory $operationHistory, $mail)
    {
        $this->request = $request;
        $this->operationHistory = $operationHistory;
        $this->mail = $mail;
    }

    public function run()
    {
        /** @var OperationHistory $operationHistory */
        $operationHistory = $this->operationHistory;
        /** @var Request $request */
        $request = $this->request;

        $sendTo = [];
        $entityManager = $this->get('doctrine.orm.entity_manager');
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
        $mail->sendMail($sendTo, $subject, $params, "mail/job.html.twig", $attachment);
    }
}