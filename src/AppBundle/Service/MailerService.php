<?php
/**
 * Created by PhpStorm.
 * User: damienlaurent
 * Date: 2019-04-19
 * Time: 21:09
 */

namespace AppBundle\Service;


use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Templating\DelegatingEngine;

class MailerService
{
    private $mailer;
    private $twig;
    private $container;
    private $em;

    public function __construct(\Swift_Mailer $mailer, DelegatingEngine $twig, Container $container, EntityManager $em)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->container = $container;
        $this->em = $em;
    }

    public function sendMail($sendTo, $subject, $params, $templateName, $attachment)
    {
        $message = new \Swift_Message();
        $dest = $sendTo[0];
        unset($sendTo[0]);
        if ($attachment != null) {
            $message
                ->setSubject($subject)
                ->setFrom($this->container->getParameter('mailer_user'))
                ->setTo($dest)
                ->setCc($sendTo)
                ->setBody($this->twig->render(
                    $templateName,
                    $params
                ), 'text/html')
                ->attach(\Swift_Attachment::fromPath($attachment));
        } else {
            $message
                ->setSubject($subject)
                ->setFrom($this->container->getParameter('mailer_user'))
                ->setTo($dest)
                ->setCc($sendTo)
                ->setBody($this->twig->render(
                    $templateName,
                    $params
                ), 'text/html');
        }
        $this->mailer->send($message);
    }

    public function generatePdfAndSendMail(OperationHistory $operationHistory)
    {
        $sendTo = [];
        $entityManager = $this->em();
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
        $error = false;
        /** @var OperationTaskHistory $task */
        foreach ($operationHistory->getTasks() as $task) {
            if ($task->getWarningIfTrue() == true && $task->getStatus() == true) {
                $error = true;
            }
        }
        if ($error) {
            $subject = "[DAMAGES] " . $operationHistory->getCustomer() . " - " . $operationHistory->getPlace();
        } else {
            $subject = $operationHistory->getCustomer() . " - " . $operationHistory->getPlace();
        }

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
            "color" => $customer->getColor(),
            "errorOnTask" => $error
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
}