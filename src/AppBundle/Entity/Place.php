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
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string")
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string")
     */
    protected $address;

    /**
     * @var GeoCoords
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\GeoCoords")
     */
    protected $geoCoords;

    /**
     * @var Operation[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Operation", mappedBy="place")
     */
    protected $operations;

    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Customer", inversedBy="places")
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
     * Set description.
     *
     * @param string $description
     *
     * @return Place
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set address.
     *
     * @param string $address
     *
     * @return Place
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
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
}
