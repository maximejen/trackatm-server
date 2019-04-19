<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @ORM\Entity
 * @Vich\Uploadable
 */
class Image
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="product_image", fileNameProperty="imageName", size="imageSize")
     *
     * @var File
     */
    private $imageFile;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $imageName;

    /**
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    private $imageSize;

    /**
     * @ORM\Column(type="datetime")
     *
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @var OperationTaskHistory
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\OperationTaskHistory", inversedBy="image")
     */
    protected $task;

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
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $imageFile
     */
    public function setImageFile($imageFile)
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile()
    {
        return $this->imageFile;
    }


    /**
     * Set imageName.
     *
     * @param string $imageName
     *
     * @return Image
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * Get imageName.
     *
     * @return string
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * Set imageSize.
     *
     * @param int $imageSize
     *
     * @return Image
     */
    public function setImageSize($imageSize)
    {
        $this->imageSize = $imageSize;

        return $this;
    }

    /**
     * Get imageSize.
     *
     * @return int
     */
    public function getImageSize()
    {
        return $this->imageSize;
    }

    /**
     * Set updatedAt.
     *
     * @param \DateTime $updatedAt
     *
     * @return Image
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }


    /**
     * Set operationTaskHistory.
     *
     * @param \AppBundle\Entity\OperationTaskHistory|null $operationTaskHistory
     *
     * @return Image
     */
    public function setOperationTaskHistory(\AppBundle\Entity\OperationTaskHistory $operationTaskHistory = null)
    {
        $this->operationTaskHistory = $operationTaskHistory;

        return $this;
    }

    /**
     * Get operationTaskHistory.
     *
     * @return \AppBundle\Entity\OperationTaskHistory|null
     */
    public function getOperationTaskHistory()
    {
        return $this->operationTaskHistory;
    }

    /**
     * Set task.
     *
     * @param \AppBundle\Entity\OperationTaskHistory|null $task
     *
     * @return Image
     */
    public function setTask(\AppBundle\Entity\OperationTaskHistory $task = null)
    {
        $this->task = $task;

        return $this;
    }

    /**
     * Get task.
     *
     * @return \AppBundle\Entity\OperationTaskHistory|null
     */
    public function getTask()
    {
        return $this->task;
    }
}
