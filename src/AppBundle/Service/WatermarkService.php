<?php


namespace AppBundle\Service;


use Ajaxray\PHPWatermark\Watermark;
use AppBundle\Entity\OperationTaskHistory;

class WatermarkService
{
    public function putWM($bgPath, $imagePath, $text, $position, $fontSize)
    {
        $watermark = new Watermark($imagePath);
        $watermark->setFontSize($fontSize)
            ->setFont('Arial')
            ->setOffset(0, 7)
            ->setStyle(Watermark::STYLE_TEXT_DARK)
            ->setPosition($position)
            ->setOpacity(1);
        $imageMark = new Watermark($imagePath);
        $imageMark
            ->setPosition($position)
            ->setOpacity(1)
            ->setOffset(0, 0)
            ->withImage($bgPath);
        $watermark->withText($text, $imagePath);
    }

    public function addWatermark($basePath, $imagePath, $version, $operationTaskHistory, $imageFile, $time)
    {
        /** @var OperationTaskHistory $operationTaskHistory */
        $mTime = $imageFile->getMTime();
//        $request->server->get('DOCUMENT_ROOT') . $request->getBasePath() . '/images/oh/' . $imageName
        $watermark = new Watermark($basePath . $imagePath);
        $watermark1 = new Watermark($basePath . $imagePath);

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

        $imageMark = new Watermark($basePath . $imagePath);
        $imageMark1 = new Watermark($basePath . $imagePath);

        $whiteImageName = $version ? "/images/white1.png" : "/images/white.png";

        $imageMark
            ->setPosition(Watermark::POSITION_BOTTOM_RIGHT)
            ->setOpacity(1)
            ->setOffset(0, 0)
            ->withImage($basePath . $whiteImageName);
        $imageMark1
            ->setPosition(Watermark::POSITION_TOP_RIGHT)
            ->setOpacity(1)
            ->setOffset(0, 0)
            ->withImage($basePath . $whiteImageName);

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

        $watermark->withText($hour, $imagePath);
        $watermark->setPosition(Watermark::POSITION_BOTTOM_LEFT);
        $watermark->withText($date, $basePath . $imagePath);
        $watermark1->withText($placeName, $basePath . $imagePath);
    }
}
