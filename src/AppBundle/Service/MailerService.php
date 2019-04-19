<?php
/**
 * Created by PhpStorm.
 * User: damienlaurent
 * Date: 2019-04-19
 * Time: 21:09
 */

namespace AppBundle\Service;


use Twig\Environment;
use Twig\TemplateWrapper;

class MailerService
{
    private $mailer;
    private $twig;

    public function __construct(\Swift_Mailer $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendMail($sendTo, $values, $templateName)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject("Hello world")
            ->setFrom("damsltc57@gmail.com")
            ->setTo($sendTo)
            ->setBody("Hello world");
        $this->mailer->send($message);
    }
}