<?php

namespace AppBundle\Controller\Home;

use AppBundle\Controller\HomeController;

use AppBundle\Entity\Customer;
use AppBundle\Entity\OperationHistory;
use \DateInterval;
use DatePeriod;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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

    /**
     * @Route("/", name="operationhistorypage")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function mainAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $customerId = $request->get('customer');
        $customer = null;
        if ($customerId != null) {
            $customer = $em->getRepository('AppBundle:Customer')->find($customerId);
        }

        $today = new \DateTime();
        $firstDate = $request->get("firstDate");
        $secondDate = $request->get("secondDate");
        $firstDate = new \DateTime($firstDate != null ? $firstDate : $today->format("Y-m-01"));
        $secondDate = $secondDate != null ? new \DateTime($secondDate) : $today->modify("last day of this month");
        $today = new \DateTime();

        if ($customer != null)
            $places = $em->getRepository('AppBundle:Place')->findBy(['customer' => $customer]);
        else
            $places = $em->getRepository('AppBundle:Place')->findAll();

        // Map all the operations into a "week"
        $week = ["Monday" => [], "Tuesday" => [], "Wednesday" => [], "Thursday" => [], "Friday" => [], "Saturday" => [], "Sunday" => []];
        if ($customer == null)
            $operations = $em->getRepository('AppBundle:Operation')->findALl();
        else
            $operations = $em->getRepository('AppBundle:Operation')->getOperationsByCustomer($customer->getId());
        foreach ($operations as $key => $operation)
            $week[ucfirst(strtolower($operation->getDay()))][] = $operation;
        // get all OperationHistories between the two dates.
        if ($customer == null)
            $histories = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesBetweenTwoDates($firstDate, $secondDate);
        else
            $histories = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesByCustomerNameAndBetweenTwoDates($customer->getName(), $firstDate, $secondDate);
        $numberDone = 0;
        $numberNotDone = 0;
        /** @var OperationHistory $history */
        foreach ($histories as $history) {
            $history->getDone() ? $numberDone++ : $numberNotDone++;
        }

        // Get all the dates between firstDate and secondDate
        $period = new DatePeriod(
            $firstDate,
            new DateInterval('P1D'),
            $secondDate
        );
        $dateArray = [];
        $numberOperations = 1;
        foreach ($period as $key => $value) {
            $interval = $value->diff($today);
            if ($week[$value->format("l")] != []) {
                $numberOperations += count($week[$value->format("l")]);
                if (intval($interval->format("%R%a")) <= 0) {
                    $dateArray[$value->format('Y-m-d')] = $week[$value->format("l")];
                }
            }
        }
        /** @var OperationHistory $history */
        foreach ($histories as $key => $history)
            $dateArray[ucfirst(strtolower($history->getBeginningDate()->format("Y-m-d")))][] = $history;

        uksort($dateArray, function ($a, $b) {
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
            "firstDate" => $firstDate,
            "secondDate" => $secondDate,
            "operationHistories" => $histories,
            "operationsPlanned" => $dateArray,
            "numberOfOperations" => $numberOperations,
            "numberOfDone" => $numberDone,
            "numberOfNotDone" => $numberNotDone,
        ]);
    }
}