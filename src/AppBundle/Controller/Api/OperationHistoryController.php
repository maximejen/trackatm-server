<?php

namespace AppBundle\Controller\Api;

use Ajaxray\PHPWatermark\Watermark;
use AppBundle\Entity\Cleaner;
use AppBundle\Entity\Image;
use AppBundle\Entity\OperationHistory;
use AppBundle\Entity\OperationTaskHistory;
use FOS\RestBundle\Controller\Annotations as Rest;
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

        $image = new Image();
        $image->setImageFile($imageFile);
        $image->setTask($operationTaskHistory);
        $operationTaskHistory->addImage($image);
        $entityManager->persist($operationTaskHistory);
        $entityManager->flush();

        $watermark = new Watermark($request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $image->getImageName());
        $watermark1 = new Watermark($request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $image->getImageName());


        $watermark->setFontSize(100)
            ->setFont('Arial')
            ->setOffset(0, 25)
            ->setStyle(Watermark::STYLE_TEXT_DARK)
            ->setPosition(Watermark::POSITION_BOTTOM_RIGHT)
            ->setOpacity(1);

        $watermark1->setFontSize(50)
            ->setFont('Arial')
            ->setOffset(0, 30)
            ->setStyle(Watermark::STYLE_TEXT_DARK)
            ->setPosition(Watermark::POSITION_TOP_RIGHT)
            ->setOpacity(1);

        $imageMark = new Watermark($request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $image->getImageName());
        $imageMark1 = new Watermark($request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $image->getImageName());

        $imageMark
            ->setPosition(Watermark::POSITION_BOTTOM_RIGHT)
            ->setOpacity(0.8)
            ->setOffset(0, 35)
            ->withImage($request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/white.png');
        $imageMark1
            ->setPosition(Watermark::POSITION_TOP_RIGHT)
            ->setOpacity(0.8)
            ->setOffset(0, 35)
            ->withImage($request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/white.png');

        if ($time == null) {
            $hour = new \DateTime(gmdate("Y-m-d\ H:i:s \G\M\T", $mTime));
        }
        else {
            $hour = new \DateTime();
            $hour->setTimestamp(floatval($time));
            $hour->setTimezone(new \DateTimezone("UTC"));
            $hour->setTimezone(new \DateTimezone("Asia/Kuwait"));
        }
        $hour = $hour->format("Y-m-d H:i:s");

        $watermark->withText($hour, $request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $image->getImageName());
        $watermark1->withText($operationTaskHistory->getOperation()->getPLace(), $request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $image->getImageName());

        return new Response(json_encode(array(
            'success' => 'true')));
    }

}