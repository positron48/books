<?php
namespace AppBundle\Repository;

use AppBundle\Entity\Book;
use Doctrine\ORM\EntityRepository;

class BookRepository extends EntityRepository
{
    /**
     * Creating a book
     * @param Book $book
     */
    public function create($book){
        $em = $this->getEntityManager();
        $em->persist($book);
        $em->flush();
    }

    /**
     * Deleting a book
     * @param Book $book
     */
    public function delete($book){
        $em = $this->getEntityManager();
        $em->remove($book);
        $em->flush();
    }

    /**
     * Return books by name
     * @param $name
     * @return array
     */
    public function findByName($name){
        return $this->findBy(array('name' => $name));
    }

    /**
     * Return books by name
     * @param $name
     * @return bool if book with the same name already exists
     */
    public function checkIfNameExists($name){
        if(empty($name)){
            throw new \InvalidArgumentException('Name can\'t be empty');
        }
        return !empty($this->findByName($name));
    }
}