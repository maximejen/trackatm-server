<?php

namespace AppBundle\Controller\Home;

use AppBundle\Controller\HomeController;

use AppBundle\Entity\OperationHistory;
use \DateInterval;
use DatePeriod;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/operation-history")
 *
 * Class PlanningController
 * @package AppBundle\Controller
 */
class OperationHistoryController extends HomeController
{
    private function getCustomer(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $customerId = $request->get('customer');
        $customer = null;
        if ($customerId != null) {
            $customer = $em->getRepository('AppBundle:Customer')->find($customerId);
        }
        return $customer;
    }

    private function getDates(Request $request) {
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

    private function getPlaces($customer) {
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
            $histories = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesBetweenTwoDates($dates[0], $dates[1]);
        else
            $histories = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesByCustomerNameAndBetweenTwoDates($customer->getName(), $dates[0], $dates[1]);
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
     * @Route("/{id}", name="operationhistory_view")
     *
     * @param OperationHistory $history
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewOperationHistory(OperationHistory $history)
    {
        $em = $this->getDoctrine()->getManager();
        $customer = $em->getRepository("AppBundle:Customer")->findBy(['name' => $history->getCustomer()]);
        return $this->render('home/operationHistory/view.html.twig', [
            'menuElements' => $this->getMenuParameters(),
            'menuMode' => "home",
            "isConnected" => !$this->getUser() == NULL,
            "history" => $history,
            "customer" => $customer
        ]);
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
    public function pdfOperationHistory(OperationHistory $history)
    {
        $em = $this->getDoctrine()->getManager();
        $customer = $em->getRepository("AppBundle:Customer")->findOneBy(['name' => $history->getCustomer()]);
        $timeSpent = $history->getEndingDate()->diff($history->getBeginningDate());
        return $this->render('home/operationHistory/job-report/job-report.html.twig', [
            "history" => $history,
            "timeSpent" => $timeSpent->h . 'h:' . $timeSpent->m . 'm',
            "completed" => $history->getEndingDate()->format("l jS F Y"),
            "color" => $customer->getColor(),
            "textColor" => $this->getGoodColorOfText($customer->getColor(), "white", "black"),
            "arrivingHour" => $history->getBeginningDate()->format('H:i'),
            "customer" => $customer
        ]);
    }
}