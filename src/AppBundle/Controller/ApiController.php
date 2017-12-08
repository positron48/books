<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Book;
use JMS\Serializer\Expression\ExpressionEvaluator;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;


class ApiController extends Controller
{

    const BOOKS_CACHE_KEY = 'books_cache';

    /**
     * @Route("/api/v{version}/{something}", name="api_404")
     */
    public function apiIndex(){
        return new JsonResponse(['success' => false, 'error' => 404, 'message' => 'Api method not exists']);
    }

    /**
     * @Route("/api/v1/books/", name="api_books")
     * @var Book $book
     */
    public function apiBooksAction(Request $request)
    {
        if (!$this->checkApiAccess($request)) {
            return new JsonResponse(['success' => false, 'error' => 401, 'message' => 'Invalid api key']);
        };

        $books = $this->get('app.books_worker')->getBooksList();

        $httpHost = $request->server->get('HTTP_HOST');
        $siteUrl = ($request->isSecure() ? 'https://' : 'http://') . $httpHost;

        foreach ($books as &$book) {
            if ($book->getFile()) {
                $book->setFile($siteUrl . '/' . $book->getFile());
            }
            if ($book->getCover()) {
                $book->setCover($siteUrl . '/' . $book->getCover());
            }
        }
        unset($book);

        $serializer = \JMS\Serializer\SerializerBuilder::create()
            ->setExpressionEvaluator(new ExpressionEvaluator(new ExpressionLanguage()))
            ->build();
        $jsonContent = $serializer->serialize($books, 'json');

        return new Response($jsonContent);
    }

    /**
     * @Route("/api/v1/books/add", name="new_api_book")
     */
    public function newApiAction(Request $request)
    {
        if (!$this->checkApiAccess($request)) {
            return new JsonResponse(['success' => false, 'error' => 401, 'message' => 'Invalid api key']);
        };

        $book = new Book();
        $book->setName($request->get('name'));
        $book->setDateRead(new \DateTime(date('Y-m-d', strtotime($request->get('date_read')))));
        if($request->get('author_ids')) {
            foreach ($request->get('author_ids') as $authorId) {
                $repository = $this->getDoctrine()->getRepository('AppBundle:Author');
                /**
                 * @var \AppBundle\Entity\Author $author
                 */
                $author = $repository->find($authorId);
                if ($author) {
                    $book->addAuthor($author);
                } else {
                    return new JsonResponse(['success' => false, 'error' => 404, 'message' => 'author not found']);
                }
            }
        }
        $book->setAllowDownload($request->get('allow_download') == true);

        if ($book->getName() && $book->getAuthors() && $book->getDateRead()) {
            $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
            $books = $repository->findBy(array('name' => $book->getName()));
            if (!empty($books)) {
                return new JsonResponse(['success' => false, 'error' => 402, 'message' => 'Book with same name already exists']);
            }

            $this->get('app.books_worker')->saveBook($book);

            $serializer = \JMS\Serializer\SerializerBuilder::create()
                ->setExpressionEvaluator(new ExpressionEvaluator(new ExpressionLanguage()))
                ->build();
            $jsonContent = $serializer->serialize($book, 'json');

            return new Response($jsonContent);
        } else {
            return new JsonResponse(['success' => false, 'error' => 403, 'message' => 'Invalid parameters']);
        }
    }

    /**
     * @Route("/api/v1/books/{id}/edit", name="edit_api_book")
     * @var \AppBundle\Entity\Author $author
     * @var \AppBundle\Entity\Book $book
     */
    public function editApiAction(Request $request, $id)
    {
        if (!$this->checkApiAccess($request)) {
            return new JsonResponse(['success' => false, 'error' => 401, 'message' => 'Invalid api key']);
        };

        $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
        $book = $repository->find($id);

        $book->setName($request->get('name'));
        $book->setDateRead(new \DateTime(date('Y-m-d', strtotime($request->get('date_read')))));

        if ($request->get('author_ids')) {
            $book->deleteAuthors();
            foreach ($request->get('author_ids') as $authorId) {
                $repository = $this->getDoctrine()->getRepository('AppBundle:Author');

                $author = $repository->find($authorId);
                if ($author) {
                    $book->addAuthor($author);
                } else {
                    return new JsonResponse(['success' => false, 'error' => 404, 'message' => 'author not found']);
                }
            }
        }
        $book->setAllowDownload($request->get('allow_download') == true);

        if ($book->getName() && $book->getAuthors() && $book->getDateRead()) {

            $this->get('app.books_worker')->saveBook($book);

            $serializer = \JMS\Serializer\SerializerBuilder::create()
                ->setExpressionEvaluator(new ExpressionEvaluator(new ExpressionLanguage()))
                ->build();
            $jsonContent = $serializer->serialize($book, 'json');

            return new Response($jsonContent);
        } else {
            return new JsonResponse(['success' => false, 'error' => 403, 'message' => 'Invalid parameters']);
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function checkApiAccess(Request $request)
    {
        return $request->get('api_key') == $this->container->getParameter('api_key');
    }
}
