<?php
/**
 * Created by PhpStorm.
 * User: damienlaurent
 * Date: 2019-04-19
 * Time: 21:09
 */

namespace AppBundle\Service;


use Symfony\Component\Templating\DelegatingEngine;

class MailerService
{
    private $mailer;
    private $twig;

    public function __construct(\Swift_Mailer $mailer, DelegatingEngine $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendMail($sendTo, $subject, $params, $templateName)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom("damsltc57@gmail.com")
            ->setTo($sendTo)
            ->setBody($this->twig->render(
                $templateName,
                $params
            ), 'text/html');
        $this->mailer->send($message);
    }
}