<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperationTaskTemplate
 *
 * @ORM\Table(name="operation_task_template")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OperationTaskTemplateRepository")
 */
class OperationTaskTemplate
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
     * @ORM\Column(name="comment", type="string", nullable=true)
     */
    protected $comment;

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
    protected $warningIfTrue;

    /**
     * @var OperationTemplate
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\OperationTemplate", inversedBy="tasks")
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
     * @return OperationTaskTemplate
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
     * @return OperationTaskTemplate
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
     * Set imagesForced.
     *
     * @param bool $imagesForced
     *
     * @return OperationTaskTemplate
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
     * @param \AppBundle\Entity\OperationTemplate|null $operation
     *
     * @return OperationTaskTemplate
     */
    public function setOperation(\AppBundle\Entity\OperationTemplate $operation = null)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get operation.
     *
     * @return \AppBundle\Entity\OperationTemplate|null
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set warningIfError.
     *
     * @param bool $warningIfTrue
     *
     * @return OperationTaskTemplate
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
