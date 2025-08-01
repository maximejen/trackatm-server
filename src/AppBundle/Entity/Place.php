<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Place
 *
 * @ORM\Table(name="place")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PlaceRepository")
 */
class Place
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
     * @var Operation[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Operation", mappedBy="place")
     */
    protected $operations;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    protected $name;

    /**
     * @var GeoCoords
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\GeoCoords", cascade={"PERSIST"}, fetch="EAGER")
     */
    protected $geoCoords;

    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Customer", inversedBy="places", fetch="EAGER")
     */
    protected $customer;


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
     * @return Place
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
     * Get identifier in name.
     *
     * @return string
     */
    public function getIdentifier()
    {
        $name = $this->name;
        $matches = [];
        preg_match("/(?<=\[).+?(?=\])/", $name, $matches);
        return "[" . $matches[0] . ']';
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getNameWithoutIdentifier()
    {
        $name = $this->name;
        return preg_replace("/\[.+?\]/", "", $name);
    }

    /**
     * Set geoCoords.
     *
     * @param GeoCoords|null $geoCoords
     *
     * @return Place
     */
    public function setGeoCoords(GeoCoords $geoCoords = null)
    {
        $this->geoCoords = $geoCoords;

        return $this;
    }

    /**
     * Get geoCoords.
     *
     * @return GeoCoords|null
     */
    public function getGeoCoords()
    {
        return $this->geoCoords;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->operations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add operation.
     *
     * @param Operation $operation
     *
     * @return Place
     */
    public function addOperation(Operation $operation)
    {
        $this->operations[] = $operation;

        return $this;
    }

    /**
     * Remove operation.
     *
     * @param Operation $operation
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOperation(Operation $operation)
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
     * Set customer.
     *
     * @param Customer|null $customer
     *
     * @return Place
     */
    public function setCustomer(Customer $customer = null)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Get customer.
     *
     * @return Customer|null
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Add operationsHistory.
     *
     * @param \AppBundle\Entity\OperationHistory $operationsHistory
     *
     * @return Place
     */
    public function addOperationsHistory(\AppBundle\Entity\OperationHistory $operationsHistory)
    {
        $this->operationsHistory[] = $operationsHistory;

        return $this;
    }

    /**
     * Remove operationsHistory.
     *
     * @param \AppBundle\Entity\OperationHistory $operationsHistory
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOperationsHistory(\AppBundle\Entity\OperationHistory $operationsHistory)
    {
        return $this->operationsHistory->removeElement($operationsHistory);
    }

    /**
     * Get operationsHistory.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOperationsHistory()
    {
        return $this->operationsHistory;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
