<?php

namespace AppBundle\Controller\Home;

use AppBundle\Controller\HomeController;

use AppBundle\Entity\OperationHistory;
use \DateInterval;
use DatePeriod;
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
        $numberNotDone = 0;
        /** @var OperationHistory $history */
        foreach ($histories as $history) {
            $history->getDone() ? $numberDone++ : $numberNotDone++;
        }

        // Get all the dates between firstDate and secondDate
        $planning = $this->getOperationsPlanning($dates[0], $dates[1], $week);
        /** @var OperationHistory $history */
        foreach ($histories as $key => $history)
            $planning[$history->getBeginningDate()->format("Y-m-d")][] = $history;

        uksort($planning, function ($a, $b) {
            $date1 = new \DateTime($a);
            $date2 = new \DateTime($b);
            return $date1 > $date2 ? 1 : -1;
        });

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
            "numberOfOperations" => 10,
            "numberOfDone" => $numberDone,
            "numberOfNotDone" => $numberNotDone,
        ]);
    }
}