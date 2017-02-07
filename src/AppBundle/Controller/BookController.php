<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Book;
use AppBundle\Form\BookType;


class BookController extends Controller
{
    /**
     * @Route("/", name="books")
     */
    public function indexAction(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:Book');
        $books = $repository->findAll();

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

            return $this->redirectToRoute('books');
        }

        return $this->render('default/books.new.html.twig', ['form' => $form->createView(), 'message' => false]);
    }
}
