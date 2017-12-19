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
            $books = $this->em->getRepository('AppBundle:Book')->findAll();

            $this->container->get('cache')->save(self::BOOKS_CACHE_KEY, serialize($books));
        }
        return $books;
    }

    /**
     * @param $book
     */
    public function saveBook($book)
    {
        /** @var \AppBundle\Repository\BookRepository $repository */
        $repository = $this->em->getRepository('AppBundle:Book');
        $repository->create($book);

        $this->clearCache(self::BOOKS_CACHE_KEY);
    }

    /**
     * @param $book
     */
    public function deleteBook($book)
    {
        /** @var \AppBundle\Repository\BookRepository $repository */
        $repository = $this->em->getRepository('AppBundle:Book');
        $repository->delete($book);

        $this->clearCache(self::BOOKS_CACHE_KEY);
    }

    /**
     * @param $cacheKey
     */
    protected function clearCache($cacheKey)
    {
        $this->container->get('cache')->delete($cacheKey);
    }
}