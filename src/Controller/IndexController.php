<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController {
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/", name="index", methods={"GET", "POST"})
     */
    public function index(){
        return $this->render('pages/index.html.twig', []);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/poster", name="poster", methods={"GET", "POST"})
     */
    public function poster(){
        return $this->render('pages/poster.html.twig', []);
    }
}