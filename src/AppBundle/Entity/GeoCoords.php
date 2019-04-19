<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GeoCoords
 *
 * @ORM\Table(name="geo_coords")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\GeoCoordsRepository")
 */
class GeoCoords
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var float
     *
     * @ORM\Column(name="lon", type="float")
     */
    protected $lon;

    /**
     * @var float
     *
     * @ORM\Column(name="lat", type="float")
     */
    protected $lat;

    /**
     * @var OperationHistory
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\OperationHistory", mappedBy="geoCoords")
     */
    protected $history;
    
    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set lon.
     *
     * @param float $lon
     *
     * @return GeoCoords
     */
    public function setLon($lon)
    {
        $this->lon = $lon;

        return $this;
    }

    /**
     * Get lon.
     *
     * @return float
     */
    public function getLon()
    {
        return $this->lon;
    }

    /**
     * Set lat.
     *
     * @param float $lat
     *
     * @return GeoCoords
     */
    public function setLat($lat)
    {
        $this->lat = $lat;

        return $this;
    }

    /**
     * Get lat.
     *
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }
}
