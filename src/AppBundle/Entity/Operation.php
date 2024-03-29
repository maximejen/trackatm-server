<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Operation
 *
 * @ORM\HasLifecycleCallbacks
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
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Place", inversedBy="operations", fetch="EAGER")
     */
    protected $place;

    /**
     * @var string
     *
     * @ORM\Column(name="day", type="string")
     */
    protected $day;

    /**
     * @var Cleaner
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Cleaner", inversedBy="operations")
     */
    protected $cleaner;

    /**
     * @var OperationTemplate
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\OperationTemplate", fetch="EAGER")
     */
    protected $template; // name, comment, tasks;

    /**
     * @var integer
     *
     * @ORM\Column(name="number_max_per_month", type="integer", nullable=true)
     */
    protected $numberMaxPerMonth;

    /**
     * @var boolean
     */
    protected $done;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=true)
     */
    protected $creationDate;

    public function setDone($done)
    {
        $this->done = $done;
    }

    public function isDone()
    {
        return $this->done;
    }


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
     * Get days.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * Set comment.
     *
     * @param string $comment
     *
     * @return Operation
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
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
     * Set day.
     *
     * @param string $day
     *
     * @return Operation
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
     * Set cleaner.
     *
     * @param \AppBundle\Entity\Cleaner|null $cleaner
     *
     * @return Operation
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

    /**
     * Set template.
     *
     * @param \AppBundle\Entity\OperationTemplate|null $template
     *
     * @return Operation
     */
    public function setTemplate(\AppBundle\Entity\OperationTemplate $template = null)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template.
     *
     * @return \AppBundle\Entity\OperationTemplate|null
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Get template name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->template->getName();
    }

    /**
     * @return Customer|null
     */
    public function getCustomer()
    {
        return $this->getPlace()->getCustomer();
    }

    public function __toString()
    {
        return $this->getPlace()->getCustomer()->getName() . " - " . $this->getCleaner() . " - " . $this->getDay() . " - " . $this->getPlace()->getName();
    }

    /**
     * Set numberMaxPerMonth.
     *
     * @param int $numberMaxPerMonth
     *
     * @return Operation
     */
    public function setNumberMaxPerMonth($numberMaxPerMonth)
    {
        $this->numberMaxPerMonth = $numberMaxPerMonth;

        return $this;
    }

    /**
     * Get numberMaxPerMonth.
     *
     * @return int
     */
    public function getNumberMaxPerMonth()
    {
        return $this->numberMaxPerMonth;
    }

    /**
     * Set creationDate.
     *
     * @param \DateTime|null $creationDate
     *
     * @return Operation
     */
    public function setCreationDate($creationDate = null)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return \DateTime|null
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersistSetCreationDate()
    {
        $this->creationDate = new \DateTime();
    }
}
