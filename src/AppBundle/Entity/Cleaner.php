<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cleaner
 *
 * @ORM\Table(name="cleaner")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CleanerRepository")
 */
class Cleaner
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
     * @var User
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Cleaner")
     */
    protected $user;

    /**
     * @var CleanerPlanningDay[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\CleanerPlanningDay", mappedBy="cleaner")
     */
    protected $planning;

    /**
     * @var OperationHistory[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\OperationHistory", mappedBy="cleaner")
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
     * Set user.
     *
     * @param User|null $user
     *
     * @return Cleaner
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->planning = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add planning.
     *
     * @param \AppBundle\Entity\CleanerPlanningDay $planning
     *
     * @return Cleaner
     */
    public function addPlanning(\AppBundle\Entity\CleanerPlanningDay $planning)
    {
        $this->planning[] = $planning;

        return $this;
    }

    /**
     * Remove planning.
     *
     * @param \AppBundle\Entity\CleanerPlanningDay $planning
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePlanning(\AppBundle\Entity\CleanerPlanningDay $planning)
    {
        return $this->planning->removeElement($planning);
    }

    /**
     * Get planning.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPlanning()
    {
        return $this->planning;
    }

    /**
     * Add history.
     *
     * @param \AppBundle\Entity\OperationHistory $history
     *
     * @return Cleaner
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
