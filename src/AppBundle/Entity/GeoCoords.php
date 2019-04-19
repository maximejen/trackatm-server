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
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->history = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add history.
     *
     * @param \AppBundle\Entity\OperationHistory $history
     *
     * @return GeoCoords
     */
    public function addHistory(\AppBundle\Entity\OperationHistory $history)
    {
        $this->history[] = $history;

        return $this;
    }

    /**
     * Remove history.
     *
     * @param \AppBundle\Entity\OperationHistory $history
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeHistory(\AppBundle\Entity\OperationHistory $history)
    {
        return $this->history->removeElement($history);
    }

    /**
     * Get history.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHistory()
    {
        return $this->history;
    }
}
