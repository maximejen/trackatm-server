<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Customer
 *
 * @ORM\Table(name="customer")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CustomerRepository")
 */
class Customer
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
     * @ORM\Column(name="email", type="string")
     */
    protected $email;

    /**
     * @var Place[]
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Place", mappedBy="customer")
     */
    protected $places;

    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string")
     */
    protected $color;

    /**
     * @var int
     *
     * @ORM\Column(name="number_max_of_operations", type="integer", nullable=true)
     */
    protected $numberMaxOfOperations;


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
     * @return Customer
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
     * Set email.
     *
     * @param string $email
     *
     * @return Customer
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->places = new ArrayCollection();
    }

    /**
     * Add place.
     *
     * @param Place $place
     *
     * @return Customer
     */
    public function addPlace(Place $place)
    {
        $this->places[] = $place;

        return $this;
    }

    /**
     * Remove place.
     *
     * @param Place $place
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePlace(Place $place)
    {
        return $this->places->removeElement($place);
    }

    /**
     * Get places.
     *
     * @return ArrayCollection|Place[]
     */
    public function getPlaces()
    {
        return $this->places;
    }

    public function __toString()
    {
       return $this->getName();
    }

    /**
     * Set color.
     *
     * @param string $color
     *
     * @return Customer
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color.
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set numberMaxOfOperations.
     *
     * @param int $numberMaxOfOperations
     *
     * @return Customer
     */
    public function setNumberMaxOfOperations($numberMaxOfOperations)
    {
        $this->numberMaxOfOperations = $numberMaxOfOperations;

        return $this;
    }

    /**
     * Get numberMaxOfOperations.
     *
     * @return int
     */
    public function getNumberMaxOfOperations()
    {
        return $this->numberMaxOfOperations;
    }
}
