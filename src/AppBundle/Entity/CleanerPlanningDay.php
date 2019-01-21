<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CleanerPlanningDay
 *
 * @ORM\Table(name="cleaner_planning_day")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CleanerPlanningDayRepository")
 */
class CleanerPlanningDay
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
     * @var string
     *
     * @ORM\Column(name="day", type="string")
     */
    protected $day;

    /**
     * @var Operation[]
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Operation", mappedBy="")
     */
    protected $operations;

    /**
     * @var Cleaner
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Cleaner", inversedBy="planning")
     */
    protected $cleaner;

    // TODO : Think if it is needed to add some operations sometimes.


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
     * Constructor
     */
    public function __construct()
    {
        $this->operations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set day.
     *
     * @param string $day
     *
     * @return CleanerPlanningDay
     */
    public function setDay($day)
    {
        $this->day = $day;

        return $this;
    }

    /**
     * Get day.
     *
     * @return string
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * Add operation.
     *
     * @param \AppBundle\Entity\Operation $operation
     *
     * @return CleanerPlanningDay
     */
    public function addOperation(\AppBundle\Entity\Operation $operation)
    {
        $this->operations[] = $operation;

        return $this;
    }

    /**
     * Remove operation.
     *
     * @param \AppBundle\Entity\Operation $operation
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOperation(\AppBundle\Entity\Operation $operation)
    {
        return $this->operations->removeElement($operation);
    }

    /**
     * Get operations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * Set cleaner.
     *
     * @param \AppBundle\Entity\Cleaner|null $cleaner
     *
     * @return CleanerPlanningDay
     */
    public function setCleaner(\AppBundle\Entity\Cleaner $cleaner = null)
    {
        $this->cleaner = $cleaner;

        return $this;
    }

    /**
     * Get cleaner.
     *
     * @return \AppBundle\Entity\Cleaner|null
     */
    public function getCleaner()
    {
        return $this->cleaner;
    }
}
