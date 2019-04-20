<?php

namespace AppBundle\Service;


use AppBundle\Entity\Operation;
use AppBundle\Entity\OperationHistory;
use AppBundle\Entity\Place;
use DateInterval;
use DatePeriod;
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

    private function returnFile($dir, $fileName)
    {
        $file = new File($this->kernel->getRootDir() . $dir . $fileName);
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        return $file;
    }

    private function getInterval(\DateTime $date1, \DateTime $date2)
    {
        $date2->modify('+1 days');
        $period = new DatePeriod(
            $date1,
            new DateInterval('P1D'),
            $date2
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

    private function generateMainColumns(\DateTime $date1, \DateTime $date2, $operations) {
        $columns = ["n" => $this->getAllPlacesConcerned($operations), "Place Name" => [], "Customer" => [], "LAT" => [], "LON" => []];
        $period = $this->getInterval($date1, $date2);
        /** @var \DateTime $date */
        foreach ($period as $date) {
            $columns[$date->format('l - Y-m-d')] = [];
        }
        $columns["TOTAL"] = [];
        return $columns;
    }

    private function getAllPlacesConcerned($operations) {
        $places = [];
        /** @var Operation $operation */
        foreach ($operations as $operation) {
            $places[$operation->getPlace()->getName()] = $operation->getPlace();
        }
        return $places;
    }

    public function mapPlacesInArray($content, $histories)
    {
        /** @var Place $place */
        foreach ($content["n"] as $place) {
            $content["Place Name"][$place->getName()] = $place->getName();
            $content["Customer"][$place->getName()] = $place->getCustomer()->getName();
            $content["LAT"][$place->getName()] = $place->getGeoCoords()->getLat();
            $content["LON"][$place->getName()] = $place->getGeoCoords()->getLon();
            $content["TOTAL"][$place->getName()] = 0;
        }
        /** @var OperationHistory $history */
        foreach ($histories as $history) {
            $cell = array_key_exists($history->getPlace(), $content[$history->getBeginningDate()->format('l - Y-m-d')]);
            if ($cell == true)
                $cell = $content[$history->getBeginningDate()->format('l - Y-m-d')][$history->getPlace()];
            if ($cell == false)
                $content[$history->getBeginningDate()->format('l - Y-m-d')][$history->getPlace()] = 1;
            else if ($cell > 0)
                $content[$history->getBeginningDate()->format('l - Y-m-d')][$history->getPlace()] += 1;
            $content["TOTAL"][$history->getPlace()] += 1;
        }
        return $content;
    }

    private function createCSV($fileName, $content, string $date1, string $date2, $customer)
    {
        $toWrite = "from;$date1;to;$date2";
        $toWrite .= $customer != null ? "customer;$customer" : "";
        $toWrite .= "\n";


        foreach ($content as $key => $item) {
            $toWrite .= $key;
            if ($key == "TOTAL")
                $toWrite .= "\n";
            else
                $toWrite .= ";";
        }
        foreach ($content["n"] as $place) {
            foreach ($content as $key => $item) {
                $exists = array_key_exists($place->getName(), $content[$key]);
                if ($exists)
                    $toWrite .= $content[$key][$place->getName()];
                else
                    $toWrite .= "";
                if ($key == "TOTAL")
                    $toWrite .= "\n";
                else
                    $toWrite .= ";";
            }
        }
        file_put_contents("csv/" . $fileName, $toWrite);
    }

    public function generateCsv(\DateTime $date1, \DateTime $date2, $histories, $operations, $customer = null)
    {
        $now = new \DateTime();
        $fileName = $now->getTimestamp() . " - csvFile.csv";

        $content = $this->generateMainColumns($date1, $date2, $operations);
        $content = $this->mapPlacesInArray($content, $histories);
        $this->createCSV($fileName, $content, $date1->format('Y-m-d'), $date2->format('Y-m-d'), $customer);

        return $this->returnFile("/../web/csv/", $fileName);
    }
}