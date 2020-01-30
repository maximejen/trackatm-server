<?php

namespace AppBundle\Controller\Home;

use AppBundle\Controller\HomeController;

use AppBundle\Entity\Image;
use AppBundle\Entity\Operation;
use AppBundle\Entity\OperationHistory;
use AppBundle\Entity\OperationTaskHistory;
use AppBundle\Form\CleanerType;
use AppBundle\Form\OperationType;
use AppBundle\Form\PlaceType;
use daandesmedt\PHPHeadlessChrome\HeadlessChrome;
use \DateInterval;
use DatePeriod;
use dawood\phpChrome\Chrome;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/operation-history")
 *
 * Class PlanningController
 * @package AppBundle\Controller
 */
class OperationHistoryController extends HomeController
{
    private function getCustomer(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $customerId = $request->get('customer');
        $customer = null;
        if ($customerId != null) {
            $customer = $em->getRepository('AppBundle:Customer')->find($customerId);
        }
        return $customer;
    }

    private function getDates(Request $request)
    {
        $today = new \DateTime();
        $firstDate = $request->get("firstDate");
        $secondDate = $request->get("secondDate");
        $firstDate = new \DateTime($firstDate != null ? $firstDate : $today->format("Y-m-01"));
        $secondDate = $secondDate != null ? new \DateTime($secondDate) : $today->modify("last day of this month");
        $interval = $secondDate->diff($firstDate);
        if (intval($interval->format("%R%a")) > 0) {
            return [$secondDate, $firstDate];
        }
        return [$firstDate, $secondDate];
    }

    private function getPlaces($customer)
    {
        $em = $this->getDoctrine()->getManager();
        if ($customer != null)
            $places = $em->getRepository('AppBundle:Place')->findBy(['customer' => $customer]);
        else
            $places = $em->getRepository('AppBundle:Place')->findAll();
        return $places;
    }

    private function getOperations($customer)
    {
        $em = $this->getDoctrine()->getManager();
        if ($customer == null)
            $operations = $em->getRepository('AppBundle:Operation')->findAll();
        else
            $operations = $em->getRepository('AppBundle:Operation')->getOperationsByCustomer($customer->getId());
        return $operations;
    }

    private function getOperationHistories($customer, $dates)
    {
        $em = $this->getDoctrine()->getManager();
        if ($customer == null)
            $histories = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesBetweenTwoBeginningDates($dates[0], $dates[1]);
        else
            $histories = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesByCustomerNameAndBetweenTwoBeginningDates($customer->getName(), $dates[0], $dates[1]);
        return $histories;
    }

    private function getWeek($operations)
    {
        $week = ["Monday" => [], "Tuesday" => [], "Wednesday" => [], "Thursday" => [], "Friday" => [], "Saturday" => [], "Sunday" => []];
        foreach ($operations as $key => $operation)
            $week[ucfirst(strtolower($operation->getDay()))][] = $operation;
        return $week;
    }

    private function getOperationsPlanning($date1, $date2, $week)
    {
        $today = new \DateTime();
        $period = new DatePeriod(
            $date1,
            new DateInterval('P1D'),
            $date2
        );
        $planning = [];
        foreach ($period as $key => $value) {
            $interval = $value->diff($today);
            if ($week[$value->format("l")] != []) {
                if (intval($interval->format("%R%a")) <= 0) {
                    $planning[$value->format('Y-m-d')] = $week[$value->format("l")];
                }
            }
        }

        return $planning;
    }

    /**
     * @Route("/", name="operationhistorypage")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function mainAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $customer = $this->getCustomer($request);
        $dates = $this->getDates($request);
        $places = $this->getPlaces($customer);
        $operations = $this->getOperations($customer);
        $histories = $this->getOperationHistories($customer, $dates);
        $week = $this->getWeek($operations);

        $numberDone = 0;
        $numberOverdue = 0;
        /** @var OperationHistory $history */
        foreach ($histories as $history) {
            $history->getDone() ? $numberDone++ : $numberOverdue++;
        }

        // Get all the dates between firstDate and secondDate
        $planning = $this->getOperationsPlanning($dates[0], $dates[1], $week);
        /** @var OperationHistory $history */
        foreach ($histories as $key => $history)
            $planning[$history->getBeginningDate()->format("Y-m-d")][] = $history;

        $count = 0;
        foreach ($planning as $item)
            $count += count($item);

        uksort($planning, function ($a, $b) {
            $date1 = new \DateTime($a);
            $date2 = new \DateTime($b);
            return $date1 > $date2 ? 1 : -1;
        });

        $fileGeneratorService = $this->container->get('file_genertor');
        if ($request->get('file') == true) {
            return $this->file($fileGeneratorService->generateCsv($dates[0], $dates[1], $histories, $operations));
        }

        return $this->render('home/operationHistory/index.html.twig', [
            'menuElements' => $this->getMenuParameters(),
            'menuMode' => "home",
            "isConnected" => !$this->getUser() == NULL,
            "numberOfCleaners" => count($em->getRepository('AppBundle:Cleaner')->findAll()),
            "numberOfPlaces" => count($places),
            "customers" => $em->getRepository('AppBundle:Customer')->findAll(),
            "selectedCustomer" => $customer,
            "firstDate" => $dates[0],
            "secondDate" => $dates[1],
            "operationHistories" => $histories,
            "operationsPlanned" => $planning,
            "numberOfOperations" => $count,
            "numberOfDone" => $numberDone,
            "numberOfNotDone" => $numberOverdue,
            "planning" => $fileGeneratorService->getPlanningPerMonths($dates[0], $dates[1], $histories, $operations)
        ]);
    }

    /**
     * @Route("/pdf", name="operationhistories_pdf")
     *
     * @param Request $request
     * @return BinaryFileResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function pdfContentAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $customer = $this->getCustomer($request);
        $dates = $this->getDates($request);
        $places = $this->getPlaces($customer);
        $operations = $this->getOperations($customer);
        $histories = $this->getOperationHistories($customer, $dates);
        $week = $this->getWeek($operations);

        $numberDone = 0;
        $numberOverdue = 0;
        /** @var OperationHistory $history */
        foreach ($histories as $history) {
            $history->getDone() ? $numberDone++ : $numberOverdue++;
        }

        // Get all the dates between firstDate and secondDate
        $planning = $this->getOperationsPlanning($dates[0], $dates[1], $week);
        /** @var OperationHistory $history */
        foreach ($histories as $key => $history)
            $planning[$history->getBeginningDate()->format("Y-m-d")][] = $history;

        $count = 0;
        foreach ($planning as $item)
            $count += count($item);

        uksort($planning, function ($a, $b) {
            $date1 = new \DateTime($a);
            $date2 = new \DateTime($b);
            return $date1 > $date2 ? 1 : -1;
        });

        $fileGeneratorService = $this->container->get('file_genertor');

        return $this->render('home/operationHistory/month-resume/month-resume.html.twig', [
            "firstDate" => $dates[0],
            "secondDate" => $dates[1],
            "planning" => $fileGeneratorService->getPlanningPerMonths($dates[0], $dates[1], $histories, $operations),
            "color" => $customer != null ? $customer->getColor() : null,
            "pdf" => true
        ]);
    }

    /**
     * @Route("/pdf/generate", name="operationhistories_pdf_generate")
     *
     * @param Request $request
     * @return BinaryFileResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function generatePdfContentAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $customer = $this->getCustomer($request);
        $dates = $this->getDates($request);
        $places = $this->getPlaces($customer);
        $operations = $this->getOperations($customer);
        $histories = $this->getOperationHistories($customer, $dates);
        $week = $this->getWeek($operations);

        $numberDone = 0;
        $numberOverdue = 0;
        /** @var OperationHistory $history */
        foreach ($histories as $history) {
            $history->getDone() ? $numberDone++ : $numberOverdue++;
        }

        // Get all the dates between firstDate and secondDate
        $planning = $this->getOperationsPlanning($dates[0], $dates[1], $week);
        /** @var OperationHistory $history */
        foreach ($histories as $key => $history)
            $planning[$history->getBeginningDate()->format("Y-m-d")][] = $history;

        $count = 0;
        foreach ($planning as $item)
            $count += count($item);

        uksort($planning, function ($a, $b) {
            $date1 = new \DateTime($a);
            $date2 = new \DateTime($b);
            return $date1 > $date2 ? 1 : -1;
        });

        $fileGeneratorService = $this->container->get('file_genertor');

        $htmlCode = $this->renderView('home/operationHistory/month-resume/month-resume.html.twig', [
            "firstDate" => $dates[0],
            "secondDate" => $dates[1],
            "planning" => $fileGeneratorService->getPlanningPerMonths($dates[0], $dates[1], $histories, $operations),
            "color" => $customer != null ? $customer->getColor() : null,
            "pdf" => true
        ]);

        $search = array(
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        );

        $replace = array(
            '>',
            '<',
            '\\1',
            ''
        );

        $htmlCode = preg_replace($search, $replace, $htmlCode);

        $today = new \DateTime();
        $fileGenerator = $this->container->get('file_genertor');

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
        $fileName = $today->getTimestamp() . ' - generated.pdf';
        file_put_contents(
            $request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/pdf/' . $fileName,
            curl_exec($ch)
        );
        curl_close($ch);

        $file = $this->file($fileGenerator->returnFile("/../web/pdf/", $fileName));
        return $file;

//        $html2pdf = $this->container->get('app.html2pdf');
//        $html2pdf->create('L', 'A3', 'fr', true, 'UTF-8', [10, 15, 10 ,15]);
//        return $html2pdf->generatePdf($htmlCode, $fileName);
    }

    /**
     * @Route("/{id}", name="operationhistory_view")
     *
     * @param OperationHistory $history
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewOperationHistory(Request $request, OperationHistory $history)
    {
        $args = array_merge([
            'menuElements' => $this->getMenuParameters(),
            'menuMode' => "home",
            "isConnected" => !$this->getUser() == NULL,
            'id' => $history->getId(),
            "oh" => $history,
            "initialDate" => $history->getInitialDate()->format("Y-m-d"),
            "lastTimeSent" => $history->getLastTimeSent()->format("Y-m-d H:i:s")
        ], $this->generateArguments($history)
        );

        $delete = $request->query->get('delete');
        if ($delete == "1") {
            /** @var OperationTaskHistory $task */
            foreach ($history->getTasks() as $task) {
                $images = $task->getImage();
                if (!$images->isEmpty()) {
                    /** @var Image $image */
                    foreach ($images->getValues() as $image) {
                        unlink($request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $image->getImageName());
                    }
                }
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($history);
            $em->flush();
            return $this->redirectToRoute("operationhistorypage");
        }

        return $this->render('home/operationHistory/view.html.twig', $args);
    }

    private function getGoodColorOfText($bgColor, $lightColor, $darkColor)
    {
        $color = ($bgColor[0] == '#') ? substr($bgColor, 1, 7) : $bgColor;
        $r = intval(substr($color, 0, 2), 16);
        $g = intval(substr($color, 2, 2), 16);
        $b = intval(substr($color, 4, 2), 16);
        return ((($r * 0.299) + ($g * 0.587) + ($b * 0.114)) > 186) ? $darkColor : $lightColor;
    }

    /**
     * @Route("/pdf/{id}", name="operationhistory_pdf")
     *
     * @param OperationHistory $history
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function pdfOperationHistory(Request $request, OperationHistory $history)
    {
        return $this->render('home/operationHistory/job-report/job-report.html.twig', $this->generateArguments($history));
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

    /**
     * @Route("/pdf/generate/{id}", name="operationhistory_pdf_generate")
     *
     * @param Request $request
     * @return BinaryFileResponse
     * @throws \Exception
     */
    public function generatePdfOperationHistory(Request $request, OperationHistory $history)
    {
        $today = new \DateTime();
        $fileGenerator = $this->container->get('file_genertor');
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
        $fileName = $today->getTimestamp() . ' - generated.pdf';
        file_put_contents(
            $request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/pdf/' . $fileName,
            curl_exec($ch)
        );
        curl_close($ch);

        $file = $this->file($fileGenerator->returnFile("/../web/pdf/", $fileName));
        return $file;
    }

    /**
     * @Route("/month-resume/send/", name="month_resume_send")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function generatePdfAndSendEmailToCustomerAction(Request $request)
    {
        $customer = $this->getCustomer($request);
        $dates = $this->getDates($request);
        $operations = $this->getOperations($customer);
        $histories = $this->getOperationHistories($customer, $dates);
        $week = $this->getWeek($operations);

        $numberDone = 0;
        $numberOverdue = 0;
        /** @var OperationHistory $history */
        foreach ($histories as $history) {
            $history->getDone() ? $numberDone++ : $numberOverdue++;
        }

        // Get all the dates between firstDate and secondDate
        $planning = $this->getOperationsPlanning($dates[0], $dates[1], $week);
        /** @var OperationHistory $history */
        foreach ($histories as $key => $history)
            $planning[$history->getBeginningDate()->format("Y-m-d")][] = $history;

        $count = 0;
        foreach ($planning as $item)
            $count += count($item);

        uksort($planning, function ($a, $b) {
            $date1 = new \DateTime($a);
            $date2 = new \DateTime($b);
            return $date1 > $date2 ? 1 : -1;
        });

        $fileGeneratorService = $this->container->get('file_genertor');

        $htmlCode = $this->renderView('home/operationHistory/month-resume/month-resume.html.twig', [
            "firstDate" => $dates[0],
            "secondDate" => $dates[1],
            "planning" => $fileGeneratorService->getPlanningPerMonths($dates[0], $dates[1], $histories, $operations),
            "color" => $customer != null ? $customer->getColor() : null,
            "pdf" => true
        ]);

        $search = array(
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        );

        $replace = array(
            '>',
            '<',
            '\\1',
            ''
        );

        $htmlCode = preg_replace($search, $replace, $htmlCode);

        $today = new \DateTime();
        $fileGenerator = $this->container->get('file_genertor');

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
        $fileName = $today->getTimestamp() . ' - generated.pdf';
        file_put_contents(
            $request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/pdf/' . $fileName,
            curl_exec($ch)
        );
        curl_close($ch);

        $file = $this->file($fileGenerator->returnFile("/../web/pdf/", $fileName));
        // TODO : send the mail with the file in attachement.

        $sendTo = [];
        $entityManager = $this->get('doctrine.orm.entity_manager');
        array_push($sendTo, $customer->getEmail());


        $admin = $entityManager
            ->getRepository('AppBundle:User')
            ->findBy(['admin' => true]);
        foreach ($admin as $item) {
            array_push($sendTo, $item->getEmail());
        }

        $numberDone = 0;
        /** @var OperationHistory $history */
        foreach ($histories as $history) {
            $history->getDone() ? $numberDone++ : $numberOverdue++;
        }

        $params = [
            "firstDate" => $dates[0],
            "secondDate" => $dates[1],
            "numberOfOperations" => $numberDone,
            "pdfFile" => $fileName
        ];

        $mail = $this->container->get('mail.send');
        $mail->sendMail($sendTo, "TrackATM - Month Resume - From " . $dates[0]->format("Y-m-d") . " to " . $dates[1]->format("Y-m-d"), $params, "mail/month-resume.html.twig", null);
        return $this->redirectToRoute("operationhistorypage");
    }

    /**
     * @Route("/create/{id}", name="operation_history_create", methods={"GET", "POST"})
     *
     * @param Request $request
     * @param Operation $operation
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createOperationHistoryAction(Request $request, Operation $operation)
    {
        $oh = new OperationHistory();
        $form = $this->createFormBuilder()
            ->add('operation', EntityType::class, [
                'class' => Operation::class,
                'data' => $operation
            ])
            ->add('mail', CheckboxType::class, [
                "required" => false
            ])
        ;
        $form = $form->getForm();
        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            /** @var EntityManager $em */
            $em = $this->get('doctrine.orm.entity_manager');

            /** @var Operation $operation */
            $operation = $form->getData()['operation'];
            $place = $operation->getPlace();
            $cleaner = $operation->getCleaner();
            $templateName = $operation->getTemplate()->getName();
            $endingDate = $request->request->get('endingDate');
            $beginningDate = $request->request->get('beginningDate');
            $date1 = new \DateTime($beginningDate);
            $date2 = new \DateTime($endingDate);
            $oh
                ->setBeginningDate($date1)
                ->setEndingDate($date2)
                ->setCleaner($cleaner)
                ->setCustomer($place->getCustomer()->getName())
                ->setPlace($place->getName())
                ->setName($templateName)
            ;

            $initialDay = $operation->getDay();
            $date = clone $oh->getBeginningDate();
            $date->setTime(0, 0, 0);
            if ($oh->getBeginningDate()->format('l') == "Sunday" && $initialDay != "Sunday") {
                $date->modify("+7 days");
                $date->modify($initialDay . " this week");
            } else if ($oh->getBeginningDate()->format('l') != "Sunday" && $initialDay == "Sunday")
                $date->modify("last sunday");
            else if ($oh->getBeginningDate()->format('l') == "Sunday" && $initialDay == "Sunday") {
                $date = clone $oh->getBeginningDate();
                $date->setTime(0, 0);
            } else
                $date->modify($initialDay . " this week");

            $oh->setInitialDate($date);
            $oh->setDone(true);

            $args = [
                ["Take picture before service", "", 1, 1, "", 0, 0],
                ["Clean ATM Glass shade", "", 1, 0, "", 1, 0],
                ["Spot removing from ATM Area", "", 1, 0, "", 2, 0],
                ["Use air blower", "", 1, 0, "", 3, 0],
                ["Remove any Gums from ATM Area", "", 1, 0, "", 4, 0],
                ["Clean ATM Keyboard with Polisher", "", 1, 0, "", 5, 0],
                ["Wipe all ATM with microfiber cloth", "", 1, 0, "", 6, 0],
                ["Empty waste bin", "", 1, 0, "", 7, 0],
                ["Take photo for any damage", "", 0, 1, "", 8, 1],
                ["Take photo after service", "", 1, 1, "", 9, 0],
            ];
            foreach ($args as $arg) {
                $task = new OperationTaskHistory();
                $task->setName($arg[0]);
                $task->setComment($arg[1]);
                $task->setStatus($arg[2]);
                $task->setImagesForced($arg[3]);
                $task->setTextInput($arg[4]);
                $task->setPosition($arg[5]);
                $task->setWarningIfTrue($arg[6]);
                $oh->addTask($task);
            }

            if ($form->getData()['mail'] == true) {
                $this->get('mail.send')->generatePdfAndSendMail($oh);
            }

            $em->persist($oh);
            $em->flush();
            return $this->redirect($this->generateUrl('operationhistorypage'));
        }

        $params = [
            'form' => $form->createView(),
            'operation' => $operation
        ];

        $now = new \DateTime();

        return $this->render(':home/operationHistory:create.html.twig', array_merge($params,
            [
                'menuElements' => $this->getMenuParameters(),
                'menuMode' => "home",
                "isConnected" => !$this->getUser() == NULL,
                'id' => 1,
                'now' => $now
            ]));
    }
}