<?php

namespace AppBundle\Service;


use AppBundle\Entity\Customer;
use AppBundle\Entity\GeoCoords;
use AppBundle\Entity\Operation;
use AppBundle\Entity\OperationHistory;
use AppBundle\Entity\Place;
use DateInterval;
use DatePeriod;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Kernel;

class FileGeneratorService
{
    private $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function returnFile($dir, $fileName)
    {
        $file = new File($this->kernel->getRootDir() . $dir . $fileName);
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        return $file;
    }

    private function getInterval(\DateTime $date1, \DateTime $date2)
    {
        $tmpDate = new \DateTime($date2->format('Y-m-d'));
        $tmpDate->modify('+1 days');
        $period = new DatePeriod(
            $date1,
            new DateInterval('P1D'),
            $tmpDate
        );
        return $period;
    }

    private function getHistoryPlanning(\DateTime $date1, \DateTime $date2, OperationHistory $histories)
    {
        $period = $this->getInterval($date1, $date2);
        $planning = [];
        foreach ($period as $key => $value)
            $planning[$value->format('Y-m-d')] = [];
        /** @var OperationHistory $history */
        foreach ($histories as $history)
            $planning[$history->getBeginningDate()->format('Y-m-d')][] = $history;
        return $planning;
    }

    private function generateEmptyTable(\DateTime $date1, \DateTime $date2, $operations, $histories)
    {
        $cols = ["id" => 0, "customer" => 1, "place" => 2, "lat" => 3, "lon" => 4];
        $places = $this->getAllPlacesConcerned($operations);
        $content = [];

        foreach ($places as $place) {
            $placeName = $place->getName();
            $content[$placeName]["id"] = $place->getIdentifier();
            $content[$placeName]["customer"] = $place->getCustomer()->getName();
            $content[$placeName]["place"] = $place->getNameWithoutIdentifier();
            $content[$placeName]["lat"] = $place->getGeoCoords()->getLat();
            $content[$placeName]["lon"] = $place->getGeoCoords()->getLon();
        }
        foreach ($histories as $history) {
            $placeName = $history->getPlace();
            $content[$placeName]["id"] = $history->getIdentifier();
            $content[$placeName]["customer"] = $history->getCustomer();
            $content[$placeName]["place"] = $history->getNameWithoutIdentifier();
            $content[$placeName]["lat"] = !array_key_exists("lat", $content[$placeName]) ? "/" : $content[$placeName]["lat"];
            $content[$placeName]["lon"] = !array_key_exists("lon", $content[$placeName]) ? "/" : $content[$placeName]["lon"];
        }

        $period = $this->getInterval($date1, $date2);
        /** @var \DateTime $date */
        foreach ($period as $date) {
            $index = intval($date->format('d'));
            $cols[$index] = count($cols);
            foreach ($content as &$item)
                $item[$index] = null;
        }
        foreach ($content as &$item)
            $item["TOTAL"] = 0;
        $cols["TOTAL"] = count($cols);
        $content["columns"] = $cols;
        return $content;
    }

    private function getAllPlacesConcerned($operations)
    {
        $places = [];
        /** @var Operation $operation */
        foreach ($operations as $operation) {
            $places[$operation->getPlace()->getName()] = $operation->getPlace();
        }
        return $places;
    }

    private function getMonthsBetweenTwoDates($date1, $date2)
    {
        $tmpDate = new \DateTime($date1->format("Y-m"));
        $months = [];
        while ($tmpDate->format('Y-m') != $date2->format('Y-m')) {
            $months[] = $tmpDate->format("Y-m");
            $tmpDate->modify('+1 months');
        }
        $months[] = $tmpDate->format("Y-m");
        return $months;
    }

    private function mapHistoriesToTable($content, $histories, $month)
    {
//        uasort($histories, function (OperationHistory $a, OperationHistory $b) {
//            return strcmp($a->getPlace(), $b->getPlace());
//        });
        /** @var OperationHistory $history */
        foreach ($histories as $history) {
            $date = $history->getBeginningDate();

            if ($date->format('Y-m') != $month) // ignore histories that are not from the current month
                continue;
            $place = $history->getPlace();
            // Change the time zone so in front it is the good hour that is printed.
            $history->getEndingDate()->setTimezone(new \DateTimeZone('Asia/Kuwait'));
            $history->getBeginningDate()->setTimezone(new \DateTimeZone('Asia/Kuwait'));

            $index = intval($date->format('d'));
            $content[$place][$index][] = $history;
            if (!array_key_exists("TOTAL", $content[$place]))
                $content[$place]["TOTAL"] = 0;
            $content[$place]["TOTAL"]++;
        }
        return $content;
    }

    public function addTotalCountLine($content)
    {
        $columns = $content['columns'];
        $newLine = ["TOTAL" => 0];
        foreach ($content as $key => $line) {
            if ($key == 'columns')
                continue;
            foreach ($line as $itemKey => $item) {
                if (!array_key_exists($itemKey, $newLine)) {
                    $newLine[$itemKey] = is_int($itemKey) ? 0 : "";
                }
                if ($itemKey == "TOTAL")
                    $newLine[$itemKey] += $item;
                if (!is_int($itemKey))
                    continue;
                if ($item != null) {
                    foreach ($item as $value)
                        $newLine[$itemKey]++;
                }
            }
        }
        $total = $newLine["TOTAL"];
        unset($newLine["TOTAL"]);
        $newLine["TOTAL"] = $total;
        $content['TOTALS'] = $newLine;
        return $content;
    }

    public function getPlanningPerMonths(\DateTime $date1, \DateTime $date2, $histories, $operations)
    {
        $months = $this->getMonthsBetweenTwoDates($date1, $date2);

        foreach ($months as $key => $month) {
            $firstDay = new \DateTime($month);
            $lastDay = new \DateTime($month);

            if ($key == 0)
                $firstDay = new \DateTime($date1->format('Y-m-d'));
            else
                $firstDay->modify("first day of this month");
            if ($key == count($months) - 1)
                $lastDay = new \DateTime($date2->format('Y-m-d'));
            else
                $lastDay->modify("last day of this month");

            $content = $this->generateEmptyTable($firstDay, $lastDay, $operations, $histories);
            $content = $this->mapHistoriesToTable($content, $histories, $month);
            $content = $this->addTotalCountLine($content);

            $monthTag = $firstDay->format("Y-m");
            $months[$key] = ["content" => $content, "date" => new \DateTime($monthTag)];
        }

        return $months;
    }

    public function generateCsv(\DateTime $date1, \DateTime $date2, $histories, $operations, $customer = null)
    {
        $now = new \DateTime();
        $fileName = $now->getTimestamp() . " - csvFile.csv";

        $content = $this->getPlanningPerMonths($date1, $date2, $histories, $operations);

        $this->createOperationHistoryCSV($fileName, $content, $date1->format('Y-m-d'), $date2->format('Y-m-d'), $customer);

        return $this->returnFile("/../web/csv/", $fileName);
    }

    public function mapPlacesInArray($content, $histories, $month, $places)
    {
        /** @var Place $place */
        foreach ($places as $key => $place) {
            $content["place"][$key] = $place->getName();
            $content["customer"][$key] = $place->getCustomer()->getName();
            $content["lat"][$key] = $place->getGeoCoords()->getLat();
            $content["lon"][$key] = $place->getGeoCoords()->getLon();
            $content["TOTAL"][$key] = 0;
        }
        /** @var OperationHistory $history */
        foreach ($histories as $history) {
            $date = $history->getBeginningDate();
            if ($date->format('Y-m') != $month)
                continue;
            $index = intval($date->format('d'));
            $cell = array_key_exists($history->getPlace(), $content[$index]);
            if ($cell == true)
                $cell = $content[$index][$history->getPlace()];
            if ($cell == false)
                $content[$index][$history->getPlace()] = 1;
            else if ($cell > 0)
                $content[$index][$history->getPlace()] += 1;
            $content["TOTAL"][$history->getPlace()] += 1;
        }
        return $content;
    }

    private function createOperationHistoryCSV($fileName, $content, string $date1, string $date2, $customer)
    {
        $toWrite = "from;$date1;to;$date2\n";

        foreach ($content as &$month) {
            $monthString = $month['date']->format('F Y');
            $toWrite .= "$monthString\n";
            $columns = $month['content']['columns'];
            foreach ($columns as $key => $item) {
                $toWrite .= $key;
                if ($key == "TOTAL")
                    $toWrite .= "\n";
                else
                    $toWrite .= ",";
            }
            unset($month['content']['columns']);
            foreach ($month['content'] as $place => $column) {
                foreach ($column as $columnKey => $item) {
                    if ($item == null)
                        $toWrite .= "";
                    else if ($item != null && gettype($item) == "array") {
                        /** @var OperationHistory $value */
                        foreach ($item as $ohkeys => $value) {
                            $toWrite .= $value->getEndingDate()->format('H:i');
                            if ($ohkeys != count($item) - 1)
                                $toWrite .= " / ";
                        }
                    }
                    else
                        $toWrite .= $item;
                    if ($columnKey == "TOTAL")
                        $toWrite .= "\n";
                    else
                        $toWrite .= ",";
                }
            }
            $toWrite .= "\n";
        }
        file_put_contents("csv/" . $fileName, $toWrite);
    }

    public function fromCSVToPlaces(EntityManager $em, $csv)
    {
        $lines = explode("\r\n", $csv);
        $columns = explode(";", $lines[0]);
        unset($lines[0]);
        foreach ($columns as $key => $column) {
            $columns[$column] = $key;
        }

        foreach ($lines as $key => $line) {
            $line = explode(";", $line);
            if (array_key_exists("id", $columns)) {
                $placeName = '[' . $line[$columns['id']] . '] ' . $line[$columns['name']];
            } else {
                $placeName = $line[$columns['name']];
            }
            $lat = $line[$columns['lat']];
            $lon = $line[$columns['lon']];
            $customerName = $line[$columns['customer']];
            $lat = str_replace(",", ".", $lat);
            $lon = str_replace(",", ".", $lon);
            $place = new Place();
            $place->setName($placeName);
            $geoCoords = new GeoCoords();
            $geoCoords->setLat(floatval($lat));
            $geoCoords->setLon(floatval($lon));
            $place->setGeoCoords($geoCoords);
            $customer = $em->getRepository('AppBundle:Customer')->findOneBy(['name' => $customerName]);
            if ($customer) {
                $place->setCustomer($customer);
                $potentialPlace = $em->getRepository('AppBundle:Place')->findOneBy(['name' => $placeName]);
                if ($potentialPlace == null) {
                    $em->persist($place);
                    $em->flush();
                }
            }
            else
                continue;
        }
    }

    public function fromCSVToOperations(EntityManager $em, $csv)
    {
        $lines = explode("\r\n", $csv);
        $columns = explode(";", $lines[0]);
        unset($lines[0]);
        foreach ($columns as $key => $column) {
            $columns[$column] = $key;
        }

        foreach ($lines as $key => $line) {
            $line = explode(";", $line);

            $operation = new Operation();

            $day = $line[$columns['day']];
            $operation->setDay($day);

            $cleaner_id = $line[$columns["cleaner_id"]];
            $cleaner = $em->getRepository('AppBundle:Cleaner')->findOneBy(['id' => $cleaner_id]);
            if (!$cleaner)
                continue;
            $operation->setCleaner($cleaner);

            $template_id = $line[$columns['template_id']];
            $template = $em->getRepository('AppBundle:OperationTemplate')->findOneBy(['id' => $template_id]);
            if (!$template)
                continue;
            $operation->setTemplate($template);

            $place_id = $line[$columns['place_id']];
            $place = $em->getRepository('AppBundle:Place')->findOneBy(['id' => $place_id]);
            if (!$place)
                continue;
            $operation->setPlace($place);

            $em->persist($operation);
            $em->flush();
        }
    }
}