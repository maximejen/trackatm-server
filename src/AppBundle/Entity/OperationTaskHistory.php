<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperationTaskHistory
 *
 * @ORM\Table(name="operation_task_history")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OperationTaskHistoryRepository")
 */
class OperationTaskHistory
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
     * @ORM\Column(name="comment", type="text")
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
     * @var bool
     *
     * @ORM\Column(name="warning_if_true", type="boolean", options={"default" : 0})
     */
    protected $warningIfTrue = false;

    /**
     * @var OperationHistory
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\OperationHistory", inversedBy="tasks")
     */
    protected $operation;


    /**
     * @var string
     *
     * @ORM\Column(name="text_input", type="string")
     */
    protected $textInput;

    /**
     * @var Image[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Image", cascade={"persist", "remove"}, mappedBy="task")
     */
    protected $image;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer")
     */
    protected $position;

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
     * @return OperationTaskHistory
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
     * @return OperationTaskHistory
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
     * @return OperationTaskHistory
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
     * @return OperationTaskHistory
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
     * @param \AppBundle\Entity\OperationHistory|null $operation
     *
     * @return OperationTaskHistory
     */
    public function setOperation(\AppBundle\Entity\OperationHistory $operation = null)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get operation.
     *
     * @return \AppBundle\Entity\OperationHistory|null
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set textInput.
     *
     * @param string $textInput
     *
     * @return OperationTaskHistory
     */
    public function setTextInput($textInput)
    {
        $this->textInput = $textInput;

        return $this;
    }

    /**
     * Get textInput.
     *
     * @return string
     */
    public function getTextInput()
    {
        return $this->textInput;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->image = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add image.
     *
     * @param \AppBundle\Entity\Image $image
     *
     * @return OperationTaskHistory
     */
    public function addImage(\AppBundle\Entity\Image $image)
    {
        $this->image[] = $image;

        return $this;
    }

    /**
     * Remove image.
     *
     * @param \AppBundle\Entity\Image $image
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeImage(\AppBundle\Entity\Image $image)
    {
        return $this->image->removeElement($image);
    }

    /**
     * Get image.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set position.
     *
     * @param int $position
     *
     * @return OperationTaskHistory
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set warningIfTrue.
     *
     * @param bool $warningIfTrue
     *
     * @return OperationTaskHistory
     */
    public function setWarningIfTrue($warningIfTrue)
    {
        $this->warningIfTrue = $warningIfTrue;

        return $this;
    }

    /**
     * Get warningIfTrue.
     *
     * @return bool
     */
    public function getWarningIfTrue()
    {
        return $this->warningIfTrue;
    }
}
