<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * OperationGroup
 */
class OperationGroup
{
    /** @var string */
    protected $name;

    /** @var Operation[] | ArrayCollection */
    protected $operations;

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param $operation
     * @return $this
     */
    public function addOperations($operation)
    {
        $this->operations[] = $operation;
        return $this;
    }

    /**
     * @param $operation
     * @return $this
     */
    public function removeOperations($operation)
    {
        $this->operations->removeElement($operation);
        return $this;
    }

    /**
     * @return Operation[]|ArrayCollection
     */
    public function getOperations()
    {
        return $this->operations;
    }
}
