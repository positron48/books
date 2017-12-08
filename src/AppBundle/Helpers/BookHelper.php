<?php
namespace AppBundle\Helpers;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

class BookHelper
{
    const BOOKS_CACHE_KEY = 'books_cache';

    private $container;
    private $em;


    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        $this->container = $container;
        $this->em = $em;
    }

    /**
     * @return array|mixed
     */
    public function getBooksList()
    {
        if ($data = $this->container->get('cache')->fetch(self::BOOKS_CACHE_KEY)) {
            $books = unserialize($data);
        } else {
            $repository = $this->em->getRepository('AppBundle:Book');
            $books = $repository->findAll();

            $this->container->get('cache')->save(self::BOOKS_CACHE_KEY, serialize($books));
        }
        return $books;
    }

    /**
     * @param $book
     */
    public function saveBook($book)
    {
        $this->em->persist($book);
        $this->em->flush();

        $this->container->get('cache')->delete(self::BOOKS_CACHE_KEY);
    }

    /**
     * @param $book
     */
    public function deleteBook($book)
    {
        $this->em->remove($book);
        $this->em->flush();

        $this->container->get('cache')->delete(self::BOOKS_CACHE_KEY);
    }
}