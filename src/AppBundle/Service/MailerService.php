<?php
/**
 * Created by PhpStorm.
 * User: damienlaurent
 * Date: 2019-04-19
 * Time: 21:09
 */

namespace AppBundle\Service;


use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Templating\DelegatingEngine;

class MailerService
{
    private $mailer;
    private $twig;
    private $container;

    public function __construct(\Swift_Mailer $mailer, DelegatingEngine $twig, Container $container)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->container = $container;
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
}