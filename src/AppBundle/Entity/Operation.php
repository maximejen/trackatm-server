<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Operation
 *
 * @ORM\Table(name="operation")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OperationRepository")
 */
class Operation
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
     * @var Place
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Place", inversedBy="operations")
     */
    protected $place;

    /**
     * @var CleanerPlanningDay[]
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\CleanerPlanningDay", inversedBy="operations")
     */
    protected $days;


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
     * Set place.
     *
     * @param Place|null $place
     *
     * @return Operation
     */
    public function setPlace(Place $place = null)
    {
        $this->place = $place;

        return $this;
    }

    /**
     * Get place.
     *
     * @return Place|null
     */
    public function getPlace()
    {
        return $this->place;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->days = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add day.
     *
     * @param \AppBundle\Entity\CleanerPlanningDay $day
     *
     * @return Operation
     */
    public function addDay(\AppBundle\Entity\CleanerPlanningDay $day)
    {
        $this->days[] = $day;

        return $this;
    }

    /**
     * Remove day.
     *
     * @param \AppBundle\Entity\CleanerPlanningDay $day
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeDay(\AppBundle\Entity\CleanerPlanningDay $day)
    {
        return $this->days->removeElement($day);
    }

    /**
     * Get days.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDays()
    {
        return $this->days;
    }
}
