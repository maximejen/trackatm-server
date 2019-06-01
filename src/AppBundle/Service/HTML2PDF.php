<?php
namespace AppBundle\Service;

class HTML2PDF {
    /** @var \Spipu\Html2Pdf\Html2Pdf */
    private $pdf;

    public function create($orientation = null, $format = null, $lang = null, $unicode = null, $encoding = null, $margin = null)
    {
        $this->pdf = new \Spipu\Html2Pdf\Html2Pdf($orientation, $format, $lang, $unicode, $encoding, $margin);
    }

    public function generatePdf($template, $name)
    {
        $this->pdf->writeHTML($template);
        return $this->pdf->Output($name . ".pdf");
    }
}