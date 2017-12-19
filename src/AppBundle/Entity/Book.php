<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Filesystem\Filesystem;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Exclude;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BookRepository")
 * @ORM\Table(name="books")
 * @ORM\HasLifecycleCallbacks
 * @ExclusionPolicy("none")
 */
class Book
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="Author", inversedBy="books", fetch="EAGER")
     * @ORM\JoinTable(name="books_authors",
     *      joinColumns={@ORM\JoinColumn(name="book_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="author_id", referencedColumnName="id")}
     *  )
     * @Assert\NotBlank()
     */
    protected $authors;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Image()
     */
    protected $cover;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\File(
     *     mimeTypes={
     *      "application/pdf",
     *      "application/msword",
     *      "text/plain",
     *      "application/x-pdf",
     *      "image/vnd.djvu",
     *      "application/octet-stream",
     *      "application/zip"
     *     },
     *     maxSize="64M"
     * )
     * @Exclude(if="!object.getAllowDownload()")
     */
    protected $file;

    /**
     * @ORM\Column(type="date")
     * @Assert\Type("\DateTime")
     */
    protected $date_read;

    /**
     * @ORM\Column(type="boolean")
     * @Exclude
     */
    protected $allow_download;

    public function __construct()
    {
        $this->authors = new ArrayCollection();
    }

    public function addAuthor(Author $author)
    {
        $author->addBook($this);
        $this->authors[] = $author;
    }

    public function deleteAuthors()
    {
        foreach ($this->authors as $author) {
            $author->removeBook($this);
        }
        $this->authors = null;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Book
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set cover
     *
     * @param string $cover
     *
     * @return Book
     */
    public function setCover($cover)
    {
        $this->cover = $cover;

        return $this;
    }

    /**
     * Get cover
     *
     * @return string
     */
    public function getCover()
    {
        return $this->cover;
    }

    /**
     * Set file
     *
     * @param string $file
     *
     * @return Book
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set dateRead
     *
     * @param \DateTime $dateRead
     *
     * @return Book
     */
    public function setDateRead($dateRead)
    {
        $this->date_read = $dateRead;

        return $this;
    }

    /**
     * Get dateRead
     *
     * @return \DateTime
     */
    public function getDateRead()
    {
        return $this->date_read;
    }

    /**
     * Set allowDownload
     *
     * @param boolean $allowDownload
     *
     * @return Book
     */
    public function setAllowDownload($allowDownload)
    {
        $this->allow_download = $allowDownload;

        return $this;
    }

    /**
     * Get allowDownload
     *
     * @return boolean
     */
    public function getAllowDownload()
    {
        return $this->allow_download;
    }

    /**
     * Remove author
     *
     * @param \AppBundle\Entity\Author $author
     */
    public function removeAuthor(\AppBundle\Entity\Author $author)
    {
        $this->authors->removeElement($author);
    }

    /**
     * Get authors
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @ORM\PostRemove
     */
    public function removeFiles()
    {
        $fs = new Filesystem();
        $files = array_filter([$this->cover, $this->file]);

        if (count($files)) {
            $fs->remove($files);
        }
    }
}
