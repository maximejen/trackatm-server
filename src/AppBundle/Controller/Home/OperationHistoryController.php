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

        if ($customer != null)
            $places = $em->getRepository('AppBundle:Place')->findBy(['customer' => $customer]);
        else
            $places = $em->getRepository('AppBundle:Place')->findAll();

        // Map all the operations into a "week"
        $week = ["Monday" => [], "Tuesday" => [], "Wednesday" => [], "Thursday" => [], "Friday" => [], "Saturday" => [], "Sunday" => []];
        $operations = $em->getRepository('AppBundle:Operation')->findAll();
        foreach ($operations as $key => $operation)
            $week[ucfirst(strtolower($operation->getDay()))][] = $operation;
        // get all OperationHistories between the two dates.
        $histories = $em->getRepository('AppBundle:OperationHistory')->findOperationHistoriesBetweenToDates($firstDate, $secondDate);
        /** @var OperationHistory $history */
        foreach ($histories as $key => $history)
            $week[ucfirst(strtolower($history->getBeginningDate()->format("l")))][] = $history;

        // Get all the dates between firstDate and secondDate
        $period = new DatePeriod(
            $firstDate,
            new DateInterval('P1D'),
            $secondDate
        );
        $dateArray = [];
        foreach ($period as $key => $value) {
            if ($week[$value->format("l")] != [])
                $dateArray[$value->format('Y-m-d')] = $week[$value->format("l")];
        }

        return $this->render('home/operationHistory/index.html.twig', [
            'menuElements' => $this->getMenuParameters(),
            'menuMode' => "home",
            "isConnected" => !$this->getUser() == NULL,
            "numberOfCleaners" => count($em->getRepository('AppBundle:Cleaner')->findAll()),
            "numberOfPlaces" => count($places),
            "customers" => $em->getRepository('AppBundle:Customer')->findAll(),
            "firstDate" => $firstDate,
            "secondDate" => $secondDate,
            "operationHistories" => $histories,
            "operationsPlanned" => $dateArray
        ]);
    }
}