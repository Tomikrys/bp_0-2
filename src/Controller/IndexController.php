<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController {
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/", methods={"GET", "POST"}, schemes={"http"})
     */
    public function index(){
        return $this->render('pages/index.html.twig', []);
    }
}