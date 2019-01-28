<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperationTask
 *
 * @ORM\Table(name="operation_task")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OperationTaskRepository")
 */
class OperationTask
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
     * @var string
     *
     * @ORM\Column(name="comment", type="string")
     */
    protected $comment;

    /**
     * @var bool
     *
     * @ORM\Column(name="status", type="boolean")
     */
    protected $status;

    /**
     * @var bool
     *
     * @ORM\Column(name="images_forced", type="boolean")
     */
    protected $imagesForced;

    /**
     * @var Operation
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Operation", inversedBy="tasks")
     */
    protected $operation;


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
     * Set name.
     *
     * @param string $name
     *
     * @return OperationTask
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
     * Set comment.
     *
     * @param string $comment
     *
     * @return OperationTask
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
     * Set status.
     *
     * @param bool $status
     *
     * @return OperationTask
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set imagesForced.
     *
     * @param bool $imagesForced
     *
     * @return OperationTask
     */
    public function setImagesForced($imagesForced)
    {
        $this->imagesForced = $imagesForced;

        return $this;
    }

    /**
     * Get imagesForced.
     *
     * @return bool
     */
    public function getImagesForced()
    {
        return $this->imagesForced;
    }

    /**
     * Set operation.
     *
     * @param \AppBundle\Entity\Operation|null $operation
     *
     * @return OperationTask
     */
    public function setOperation(\AppBundle\Entity\Operation $operation = null)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get operation.
     *
     * @return \AppBundle\Entity\Operation|null
     */
    public function getOperation()
    {
        return $this->operation;
    }
}
