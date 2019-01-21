<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperationHistory
 *
 * @ORM\Table(name="operation_history")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OperationHistoryRepository")
 */
class OperationHistory
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
     * @ORM\Column(name="name", type="string")
     */
    protected $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="beginning_date", type="datetime")
     */
    protected $beginningDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ending_date", type="datetime")
     */
    protected $endingDate;

    /**
     * @var GeoCoords
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\GeoCoords")
     */
    protected $geoCoords;

    /**
     * @var OperationTaskHistory[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\OperationTaskHistory", mappedBy="operation")
     */
    protected $tasks;

    /**
     * @var Cleaner
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Cleaner", inversedBy="history")
     */
    protected $cleaner;


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
        $this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return OperationHistory
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set beginningDate.
     *
     * @param \DateTime $beginningDate
     *
     * @return OperationHistory
     */
    public function setBeginningDate($beginningDate)
    {
        $this->beginningDate = $beginningDate;

        return $this;
    }

    /**
     * Get beginningDate.
     *
     * @return \DateTime
     */
    public function getBeginningDate()
    {
        return $this->beginningDate;
    }

    /**
     * Set endingDate.
     *
     * @param \DateTime $endingDate
     *
     * @return OperationHistory
     */
    public function setEndingDate($endingDate)
    {
        $this->endingDate = $endingDate;

        return $this;
    }

    /**
     * Get endingDate.
     *
     * @return \DateTime
     */
    public function getEndingDate()
    {
        return $this->endingDate;
    }

    /**
     * Set geoCoords.
     *
     * @param \AppBundle\Entity\GeoCoords|null $geoCoords
     *
     * @return OperationHistory
     */
    public function setGeoCoords(\AppBundle\Entity\GeoCoords $geoCoords = null)
    {
        $this->geoCoords = $geoCoords;

        return $this;
    }

    /**
     * Get geoCoords.
     *
     * @return \AppBundle\Entity\GeoCoords|null
     */
    public function getGeoCoords()
    {
        return $this->geoCoords;
    }

    /**
     * Add task.
     *
     * @param \AppBundle\Entity\OperationTaskHistory $task
     *
     * @return OperationHistory
     */
    public function addTask(\AppBundle\Entity\OperationTaskHistory $task)
    {
        $this->tasks[] = $task;

        return $this;
    }

    /**
     * Remove task.
     *
     * @param \AppBundle\Entity\OperationTaskHistory $task
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeTask(\AppBundle\Entity\OperationTaskHistory $task)
    {
        return $this->tasks->removeElement($task);
    }

    /**
     * Get tasks.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * Set cleaner.
     *
     * @param \AppBundle\Entity\Cleaner|null $cleaner
     *
     * @return OperationHistory
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
