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
use AppBundle\Form\BookType;
use JMS\Serializer\Expression\ExpressionEvaluator;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;


class BookController extends Controller
{

    const BOOKS_CACHE_KEY = 'books_cache';

    /**
     * @Route("/", name="books")
     */
    public function indexAction(Request $request)
    {
        $books = $this->getBooksList();
        return $this->render('default/index.html.twig', ['books' => $books]);
    }

    /**
     * @Route("/api/v1/books/", name="api_books")
     * @var Book $book
     */
    public function apiBooksAction(Request $request)
    {
        if(!$this->checkApiAccess($request)){
            return new JsonResponse(['success' => false, 'error' => 401, 'message' => 'Invalid api key']);
        };

        $books = $this->getBooksList();

        $siteUrl = $this->container->getParameter('site_url');
        foreach ($books as &$book) {
            if($book->getFile()){
                $book->setFile($siteUrl.'/'.$book->getFile());
            }
            if($book->getCover()){
                $book->setCover($siteUrl.'/'.$book->getCover());
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
     * @Route("/book/new/", name="new_book")
     */
    public function newAction(Request $request)
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
            $books = $repository->findBy(array('name' => $book->getName()));
            if(!empty($books)){
                return $this->render('default/books.new.html.twig', [
                    'form' => $form->createView(),
                    'message' => 'library.books.exists'
                ]);
            }

            $file = $book->getCover();
            $coverFileName = $this->get('app.covers_uploader')->upload($file);
            $book->setCover($coverFileName);

            $file = $book->getFile();
            $fileFileName = $this->get('app.books_uploader')->upload($file);
            $book->setFile($fileFileName);

            $this->saveBook($book);

            return $this->redirectToRoute('books');
        }

        return $this->render('default/books.new.html.twig', ['form' => $form->createView(), 'message' => false]);
    }

    /**
     * @Route("/api/v1/books/add", name="new_api_book")
     */
    public function newApiAction(Request $request)
    {
        if(!$this->checkApiAccess($request)){
            return new JsonResponse(['success' => false, 'error' => 401, 'message' => 'Invalid api key']);
        };

        $book = new Book();
        $book->setName($request->get('name'));
        $book->setDateRead(new \DateTime(date('Y-m-d', strtotime($request->get('date_read')))));
        foreach ($request->get('author_ids') as $authorId) {
            $repository = $this->getDoctrine()->getRepository('AppBundle:Author');
            /**
             * @var \AppBundle\Entity\Author $author
             */
            $author = $repository->find($authorId);
            if($author){
                $book->addAuthor($author);
            }else{
                return new JsonResponse(['success' => false, 'error' => 404, 'message' => 'author not found']);
            }
        }
        $book->setAllowDownload($request->get('allow_download') == true);

        if($book->getName() && $book->getAuthors() && $book->getDateRead()){
            $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
            $books = $repository->findBy(array('name' => $book->getName()));
            if(!empty($books)){
                return new JsonResponse(['success' => false, 'error' => 402, 'message' => 'Book with same name already exists']);
            }

            $this->saveBook($book);

            $serializer = \JMS\Serializer\SerializerBuilder::create()
                ->setExpressionEvaluator(new ExpressionEvaluator(new ExpressionLanguage()))
                ->build();
            $jsonContent = $serializer->serialize($book, 'json');

            return new Response($jsonContent);
        }else{
            return new JsonResponse(['success' => false, 'error' => 403, 'message' => 'Invalid parameters']);
        }
    }

    /**
     * @Route("/book/edit/{id}/", name="edit_book")
     */
    public function editAction(Request $request, $id){
        $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
        $book = $repository->find($id);

        if(empty($book)){
            return $this->render('errors/404.html.twig');
        }else{
            $filePath = $book->getFile();
            if($filePath) {
                try {
                    $file = new File($this->container->getParameter('absolute_dir') . '/' . $filePath);
                    $book->setFile($file);
                }catch(FileException $ex){
                    $book->setFile(null);
                }
            }else{
                $book->setFile(null);
            }

            $coverPath = $book->getCover();
            if($coverPath) {
                try {
                    $file = new File($this->container->getParameter('absolute_dir') . '/' . $coverPath);
                    $book->setCover($file);
                }catch(FileException $ex){
                    $book->setCover(null);
                }
            }else{
                $book->setCover(null);
            }
        }

        $form = $this->createForm(BookType::class, $book);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //если ничего не менялось - файл пуст, а служебное поле заполнено - устанавливаем текущие данные книги
            $coverTitle = $request->get('cover_title');
            if(!$book->getCover() instanceof UploadedFile && !empty($coverTitle)){
                $book->setCover($coverPath);
            }

            $fileTitle = $request->get('file_title');
            if(!$book->getFile() instanceof UploadedFile && !empty($fileTitle)){
                $book->setFile($filePath);
            }

            $this->saveBook($book);

            return $this->redirectToRoute('books');
        }

        return $this->render('default/books.new.html.twig', ['form' => $form->createView(), 'message' => false]);
    }

    /**
     * @Route("/api/v1/books/{id}/edit", name="edit_api_book")
     * @var \AppBundle\Entity\Author $author
     * @var \AppBundle\Entity\Book $book
     */
    public function editApiAction(Request $request, $id)
    {
        if(!$this->checkApiAccess($request)){
            return new JsonResponse(['success' => false, 'error' => 401, 'message' => 'Invalid api key']);
        };

        $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
        $book = $repository->find($id);

        $book->setName($request->get('name'));
        $book->setDateRead(new \DateTime(date('Y-m-d', strtotime($request->get('date_read')))));

        if($request->get('author_ids')) {
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

        if($book->getName() && $book->getAuthors() && $book->getDateRead()){

            $this->saveBook($book);

            $serializer = \JMS\Serializer\SerializerBuilder::create()
                ->setExpressionEvaluator(new ExpressionEvaluator(new ExpressionLanguage()))
                ->build();
            $jsonContent = $serializer->serialize($book, 'json');

            return new Response($jsonContent);
        }else{
            return new JsonResponse(['success' => false, 'error' => 403, 'message' => 'Invalid parameters']);
        }
    }

    /**
     * @Route("/book/delete/{id}/", name="delete_book")
     */
    public function deleteAction(Request $request, $id){
        $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
        $book = $repository->find($id);

        $em = $this->getDoctrine()->getManager();
        $em->remove($book);

        $em->flush();

        $this->get('cache')->delete(self::BOOKS_CACHE_KEY);

        return $this->redirectToRoute('books');
    }

    /**
     * @return array|mixed
     */
    protected function getBooksList()
    {
        if ($data = $this->get('cache')->fetch(self::BOOKS_CACHE_KEY)) {
            $books = unserialize($data);
        } else {
            $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
            $books = $repository->findAll();

            $this->get('cache')->save(self::BOOKS_CACHE_KEY, serialize($books));
        }
        return $books;
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function checkApiAccess(Request $request)
    {
        return $request->get('api_key') == $this->container->getParameter('api_key');
    }

    /**
     * @param $book
     */
    protected function saveBook($book)
    {
        $em = $this->getDoctrine()->getManager();
        $em->persist($book);
        $em->flush();

        $this->get('cache')->delete(self::BOOKS_CACHE_KEY);
    }
}
