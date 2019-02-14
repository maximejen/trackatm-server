<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperationTemplate
 *
 * @ORM\Table(name="operation_template")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OperationTemplateRepository")
 */
class OperationTemplate
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
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Place")
     */
    protected $place;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text")
     */
    protected $comment;

    /**
     * @var OperationTaskTemplate[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\OperationTaskTemplate", mappedBy="operation", cascade={"PERSIST", "REMOVE"})
     */
    protected $tasks;


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
     * Set comment.
     *
     * @param string $comment
     *
     * @return OperationTemplate
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
     * Set place.
     *
     * @param \AppBundle\Entity\Place|null $place
     *
     * @return OperationTemplate
     */
    public function setPlace(\AppBundle\Entity\Place $place = null)
    {
        $this->place = $place;

        return $this;
    }

    /**
     * Get place.
     *
     * @return \AppBundle\Entity\Place|null
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Add task.
     *
     * @param \AppBundle\Entity\OperationTaskTemplate $task
     *
     * @return OperationTemplate
     */
    public function addTask(\AppBundle\Entity\OperationTaskTemplate $task)
    {
        $this->tasks[] = $task;

        return $this;
    }

    /**
     * Remove task.
     *
     * @param \AppBundle\Entity\OperationTaskTemplate $task
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeTask(\AppBundle\Entity\OperationTaskTemplate $task)
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
     * Set name.
     *
     * @param string $name
     *
     * @return OperationTemplate
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
}
