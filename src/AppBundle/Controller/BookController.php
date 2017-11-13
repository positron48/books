<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Book;
use AppBundle\Form\BookType;


class BookController extends Controller
{

    const BOOKS_CACHE_KEY = 'books_cache';

    /**
     * @Route("/", name="books")
     */
    public function indexAction(Request $request)
    {
        if ($data = $this->get('cache')->fetch(self::BOOKS_CACHE_KEY)) {
            $books = unserialize($data);
        } else {
            $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
            $books = $repository->findAll();

            $this->get('cache')->save(self::BOOKS_CACHE_KEY, serialize($books));
        }


        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', ['books' => $books]);
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

            $em = $this->getDoctrine()->getManager();
            $em->persist($book);
            $em->flush();

            $this->get('cache')->delete(self::BOOKS_CACHE_KEY);

            return $this->redirectToRoute('books');
        }

        return $this->render('default/books.new.html.twig', ['form' => $form->createView(), 'message' => false]);
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
                $book->setFile(new File(
                        $this->container->getParameter('absolute_upload_dir') . '/' .
                        $this->container->getParameter('books_upload_dir') . '/' .
                        $filePath)
                );
            }else{
                $book->setFile(null);
            }

            $coverPath = $book->getCover();
            if($coverPath) {
                $book->setCover(new File(
                    $this->container->getParameter('absolute_upload_dir').'/'.
                    $this->container->getParameter('covers_upload_dir').'/'.
                    $coverPath)
                );
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

            $em = $this->getDoctrine()->getManager();
            $em->persist($book);
            $em->flush();

            $this->get('cache')->delete(self::BOOKS_CACHE_KEY);

            return $this->redirectToRoute('books');
        }

        return $this->render('default/books.new.html.twig', ['form' => $form->createView(), 'message' => false]);
    }
}
