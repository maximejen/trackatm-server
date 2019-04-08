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
     * @var OperationHistory[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\OperationHistory", mappedBy="cleaner")
     */
    protected $history;

    /**
     * @var Operation[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Operation", mappedBy="cleaner")
     */
    protected $operations;

    /**
     * @var User
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\User")
     */
    protected $user;


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

    /**
     * Add operation.
     *
     * @param \AppBundle\Entity\Operation $operation
     *
     * @return Cleaner
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

    public function __toString()
    {
        return "Cleaner #" . $this->getId();
    }
}
