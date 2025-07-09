<?php

namespace AppBundle\Controller\Api;

use Ajaxray\PHPWatermark\Watermark;
use AppBundle\Entity\Cleaner;
use AppBundle\Entity\Customer;
use AppBundle\Entity\Image;
use AppBundle\Entity\OperationHistory;
use AppBundle\Entity\OperationTaskHistory;
use AppBundle\Entity\OperationTaskTemplate;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations as Rest;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class OperationHistoryController
 * @package AppBundle\Controller\Api
 */
class OperationHistoryController extends ApiController
{

    public function generatePdfAndSendMail(Request $request, OperationHistory $operationHistory)
    {
        $sendTo = [];
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var Customer $customer */
        $customer = $entityManager
            ->getRepository('AppBundle:Customer')
            ->findOneBy(['name' => $operationHistory->getCustomer()]);
        $emails = $customer->getEmail();
        $emails = explode(";", $emails);
        foreach ($emails as $email) {
            array_push($sendTo, $email);
        }


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

    /**
     * @Rest\View(serializerGroups={"cleaner"})
     * @Rest\Post("/api/operation/history/create/{id}")
     *
     * @param Request $request
     * @param Cleaner $cleaner
     * @param SerializerInterface $serializer
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createOperationHistoryAction(Request $request, Cleaner $cleaner, SerializerInterface $serializer)
    {
        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);
//        try {
            $params = array();
            $content = $request->getContent(); //  $this->get("request")->getContent();
            if (!empty($content)) {
                $params = json_decode($content, true); // 2nd param to get as array
            }


            if (!array_key_exists('beginningDate', $params) || !array_key_exists('endingDate', $params)
                || !array_key_exists('operationId', $params)
                || !array_key_exists('operationTemplateId', $params)) {
                $response = new Response();
                $response->setStatusCode('400');
                $response->setContent(json_encode(array(
                    'success' => false,
                    'message' => 'Invalid request')));
                return $response;
            }

            $entityManager = $this->get('doctrine.orm.entity_manager');
            $operation = $entityManager
                ->getRepository('AppBundle:Operation')
                ->findOneBy(['id' => $params['operationId']]);

            $operationTemplate = $entityManager
                ->getRepository('AppBundle:OperationTemplate')
                ->findOneBy(['id' => $params['operationTemplateId']]);

            $place = $operation->getPlace();
            $customer = $place->getCustomer();
            if (!$operation || !$operationTemplate)
                return new JsonResponse(['message' => "Operation not found"], 404);

            $history = new OperationHistory();
            $history->setName($operationTemplate->getName());
            $history->setPlace($place->getName());
            $history->setCustomer($customer->getName());
            $history->setGeoCoords($place->getGeoCoords());
            $history->setCleaner($cleaner);
            $history->setDone(true);
            $date = new \DateTime();
            $date->setTimestamp($params['beginningDate']);
            $history->setBeginningDate($date);
            $date1 = new \DateTime();
            $date1->setTimestamp($params['endingDate']);
            $history->setEndingDate($date1);

            $typicalWeek = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
            $isADay = false;
            foreach ($typicalWeek as $day) !$isADay && $isADay = $params['initialDate'] == $day;
            if (!$isADay) {
                $date = new \DateTime($params['initialDate']);
            }
            else {
                $date = clone $history->getBeginningDate();
                $date->setTime(0, 0, 0);
                if ($history->getBeginningDate()->format('l') == "Sunday" && $params['initialDate'] != "Sunday") {
                    $date->modify("+7 days");
                    $date->modify($params['initialDate'] . " this week");
                } else if ($history->getBeginningDate()->format('l') != "Sunday" && $params['initialDate'] == "Sunday")
                    $date->modify("last sunday");
                else if ($history->getBeginningDate()->format('l') == "Sunday" && $params['initialDate'] == "Sunday") {
                    $date = clone $history->getBeginningDate();
                    $date->setTime(0, 0);
                } else
                    $date->modify($params['initialDate'] . " this week");
            }
            $history->setInitialDate($date);

            /** @var ArrayCollection | OperationTaskTemplate[] $taskTemplates */
            $taskTemplates = $entityManager->getRepository("AppBundle:OperationTaskTemplate")->findAll();

            foreach ($params['tasks'] as $key => $param) {
                $task = new OperationTaskHistory();
                $task->setName($param["key"]);
                $task->setComment($param["comment"] ? $param["comment"] : "");
                $task->setStatus($param["checked"]);
                $task->setImagesForced($param["imageForced"]);
                $task->setTextInput($param["text"]);
                $task->setPosition($key);
                $history->addTask($task);
                $taskTemplate = array_filter(
                    $taskTemplates,
                    function ($opTaskTemp) use ($param) {
                        /** @var OperationTaskTemplate $opTaskTemp */
                        return $opTaskTemp->getName() == $param["key"];
                    }
                );

                if (is_array($taskTemplate) && count($taskTemplate) > 0) {
                    $task->setWarningIfTrue($taskTemplate[array_key_first($taskTemplate)]->getWarningIfTrue());
                }
            }


            $entityManager->persist($history);
            $entityManager->flush();
            $id = $history->getId();


            $file = "oh.log";
            if (!file_exists($file))
                fopen($file, "w");
            $current = file_get_contents($file);
            $current .= "\n=== Creating OH ===\n";
            file_put_contents($file, $current);
            $now = new \DateTime();
            $current = file_get_contents($file);
            $current .= "OH ID : " . $history->getId() . "\n" . "Place : " . $history->getPlace() . "\n";
            $current .= "CL : " . $cleaner->getId() . "\n" . "DATE : " . $now->format("Y-m-d H:i:s\n");
            file_put_contents($file, $current);

            $tasksIds = [];
            $current = file_get_contents($file);
            $current .= "=== Add tasks to OH " . $history->getId() . " ===\n";
            file_put_contents($file, $current);
            foreach ($history->getTasks() as $task) {
                $tasksIds[] = $task->getId();
                $current = file_get_contents($file);
                $current .= "Task : " . $task->getName() . "\nTask ID :" . $task->getId() . "\n";
                file_put_contents($file, $current);
            }



        try {
            $mail = $this->get('mail.send');
            $mail->generatePdfAndSendMail($history);
        } catch (Exception $e) {
            $file = "mail.log";
            if (!file_exists($file))
                fopen($file, "w");
            $current = file_get_contents($file);
            $current .= "ERROR : " . $e->getMessage() . "\n";
            file_put_contents($file, $current);
        }

        return new Response(json_encode(array(
            'success' => 'true',
            'historyId' => $id,
            "tasksIds" => $tasksIds
        )));
    }


    /**
     * @Rest\View(serializerGroups={"cleaner"})
     * @Rest\Post("/api/operation/history/{id}")
     *
     * @param Request $request
     * @param Cleaner $cleaner
     * @param SerializerInterface $serializer
     * @return Response
     */
    public function postOperationHistoryAction(Request $request, Cleaner $cleaner, SerializerInterface $serializer)
    {
        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);
        $params = array();
        $content = $request->getContent(); //  $this->get("request")->getContent();
        if (!empty($content)) {
            $params = json_decode($content, true); // 2nd param to get as array
        }

        if (!array_key_exists('beginningDate', $params) || !array_key_exists('endingDate', $params)
            || !array_key_exists('operationId', $params)
            || !array_key_exists('operationTemplateId', $params)) {
            $response = new Response();
            $response->setStatusCode('400');
            $response->setContent(json_encode(array(
                'success' => false,
                'message' => 'Invalid request')));
            return $response;
        }

        $file = "oh.log";
        if (!file_exists($file))
            fopen($file, "w");
        $current = file_get_contents($file);
        $current .= "\n=== Creating OH ===\n";
        file_put_contents($file, $current);

        $entityManager = $this->get('doctrine.orm.entity_manager');
        $operation = $entityManager
            ->getRepository('AppBundle:Operation')
            ->findOneBy(['id' => $params['operationId']]);

        $operationTemplate = $entityManager
            ->getRepository('AppBundle:OperationTemplate')
            ->findOneBy(['id' => $params['operationTemplateId']]);

        $place = $operation->getPlace();
        $customer = $place->getCustomer();
        if (!$operation || !$operationTemplate)
            return new JsonResponse(['message' => "Operation not found"], 404);

        $history = new OperationHistory();
        $history->setName($operationTemplate->getName());
        $history->setPlace($place->getName());
        $history->setCustomer($customer->getName());
        $history->setGeoCoords($place->getGeoCoords());
        $history->setCleaner($cleaner);
        $history->setDone(true);
        $history->setInitialDate(new \DateTime($params['initialDate'] . " this week"));

        $date = new \DateTime();
        $date->setTimestamp($params['beginningDate']);
        $history->setBeginningDate($date);
        $date1 = new \DateTime();
        $date1->setTimestamp($params['endingDate']);
        $history->setEndingDate($date1);

        $entityManager->persist($history);
        $entityManager->flush();
        $id = $history->getId();

        $now = new \DateTime();
        $current = file_get_contents($file);
        $current .= "OH ID : " . $history->getId() . "\n" . "Place : " . $history->getPlace() . "\n";
        $current .= "CL : " . $cleaner->getId() . "\n" . "DATE : " . $now->format("Y-m-d H:i:s\n");
        file_put_contents($file, $current);


        try {
            $mail = $this->get('mail.send');
            $mail->generatePdfAndSendMail($history);
        } catch (Exception $e) {
            $file = "mail.log";
            if (!file_exists($file))
                fopen($file, "w");
            $current = file_get_contents($file);
            $current .= "ERROR : " . $e->getMessage() . "\n";
            file_put_contents($file, $current);
        }

        return new Response(json_encode(array(
            'success' => 'true',
            'historyId' => $id)));
    }

    /**
     * @Rest\View(serializerGroups={"operationhistory"})
     * @Rest\Post("/api/operation/task/{id}")
     *
     * @param Request $request
     * @param OperationHistory $operationHistory
     * @param SerializerInterface $serializer
     * @return JsonResponse|Response
     */
    public function postTaskAction(Request $request, OperationHistory $operationHistory, SerializerInterface $serializer)
    {
        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);

        $entityManager = $this->get('doctrine.orm.entity_manager');

        $task = new OperationTaskHistory();

        $task->setName($request->request->get("name"));
        $task->setComment($request->request->get("comment"));
        $task->setStatus($request->request->get("checked"));
        $task->setImagesForced($request->request->get("imagesForced"));
        $task->setTextInput($request->request->get("textInput"));
        $task->setPosition($request->request->get("position"));

        $operationHistory->addTask($task);
        $entityManager->persist($operationHistory);
        $entityManager->flush();
        $id = $task->getId();

        $file = "oh.log";
        if (!file_exists($file))
            fopen($file, "w");
        $current = file_get_contents($file);
        $current .= "\n=== Add task to OH " . $operationHistory->getId() . " ===\n";
        $current .= "Task : " . $task->getName() . "\n";
        file_put_contents($file, $current);

        return new Response(json_encode(array(
            'success' => 'true',
            'taskId' => $id
        )));
    }

    /**
     * @Rest\View(serializerGroups={"operationtaskhistory"})
     * @Rest\Post("/api/operation/image/{id}")
     *
     * @param Request $request
     * @param OperationTaskHistory $operationTaskHistory
     * @param SerializerInterface $serializer
     * @return JsonResponse|Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postImageAction(Request $request, OperationTaskHistory $operationTaskHistory, SerializerInterface $serializer)
    {
        date_default_timezone_set('Europe/Paris');

        if (!$this->checkUserIsConnected($request))
            return new JsonResponse(['message' => "you need to be connected"], 403);
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /**  @var $image UploadedFile */
        $imageFile = $request->files->get("image");
        $mTime = $imageFile->getMTime();
        $time = $request->query->get('timestamp');

        $version = $request->query->get("version");

        $image = new Image();
        $image->setImageFile($imageFile);
        $image->setTask($operationTaskHistory);
        $operationTaskHistory->addImage($image);
        $entityManager->persist($operationTaskHistory);
        $entityManager->flush();

        $imageName = $image->getImageName();
        $watermark = new Watermark($request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $imageName);
        $watermark1 = new Watermark($request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $imageName);

        $fontSize = $version ? 10 : 35;
        $offset = $version ? 42 : 65;
        $offset = 0;

        $watermark->setFontSize($fontSize)
            ->setFont('Arial')
            ->setOffset(0, 7)
            ->setStyle(Watermark::STYLE_TEXT_DARK)
            ->setPosition(Watermark::POSITION_BOTTOM_RIGHT)
            ->setOpacity(1);

        $watermark1->setFontSize($fontSize)
            ->setFont('Arial')
            ->setOffset(0, 7)
            ->setStyle(Watermark::STYLE_TEXT_DARK)
            ->setPosition(Watermark::POSITION_TOP_RIGHT)
            ->setOpacity(1);

        $date = new \DateTime();

        $imageMark = new Watermark($request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $imageName);
        $imageMark1 = new Watermark($request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $imageName);

        $whiteImageName = $version ? "/images/white1.png" : "/images/white.png";

        $imageMark
            ->setPosition(Watermark::POSITION_BOTTOM_RIGHT)
            ->setOpacity(1)
            ->setOffset(0, 0)
            ->withImage($request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . $whiteImageName);
        $imageMark1
            ->setPosition(Watermark::POSITION_TOP_RIGHT)
            ->setOpacity(1)
            ->setOffset(0, 0)
            ->withImage($request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . $whiteImageName);

        if ($time == null) {
            $hour = new \DateTime(gmdate("Y-m-d\ H:i:s \G\M\T", $mTime));
        } else {
            $hour = new \DateTime();
            $hour->setTimestamp(floatval($time));
            $hour->setTimezone(new \DateTimezone("UTC"));
            $hour->setTimezone(new \DateTimezone("Asia/Kuwait"));
        }
//        $hour = $hour->format("Y-m-d H:i:s");

        $placeName = strtoupper($operationTaskHistory->getOperation()->getPLace() . " ");
        $date = strtoupper($hour->format(" l jS M Y"));
        $hour = strtoupper($hour->format("H:i "));

        $watermark->withText($hour, $request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $imageName);
        $watermark->setPosition(Watermark::POSITION_BOTTOM_LEFT);
        $watermark->withText($date, $request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $imageName);
        $watermark1->withText($placeName, $request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $imageName);

        $file = "oh.log";
        if (!file_exists($file))
            fopen($file, "w");
        $current = file_get_contents($file);
        $current .= "\n=== Add image to Task " . $operationTaskHistory->getId() . "#" . $operationTaskHistory->getPosition() . " for OH " . $operationTaskHistory->getOperation()->getId() . " ===\n";
        $current .= "Task : " . $operationTaskHistory->getName() . "\n";
        $current .= "Image : " . $imageName . "\n";
        $current .= "Link : " . "https://track-atm.com/images/oh/" . $imageName . "\n";
        $current .= "Version : " . $version . "\n";
        $current .= "Image Path : " . $request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . $whiteImageName . "\n";
        file_put_contents($file, $current);

        return new Response(json_encode(array(
            'success' => 'true')));
    }

}
