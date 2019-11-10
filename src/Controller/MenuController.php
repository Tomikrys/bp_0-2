<?php


namespace App\Controller;


use App\Entity\Type;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MenuController
 * @package App\Controller
 */
class MenuController extends AbstractController {
    /**
     * @Route("/menu", methods={"GET", "POST"})
     */
    public function index(){
        // naplnění struktury pro výpis tabulky
        $types = $this->getDoctrine()->getRepository(Type::class)->findAll();
        $foods = null;
        $type = null;
        foreach ($types as $type) {
            $foods_of_type = $type->getFoods();
            $foods[$type->getName()] = $foods_of_type;
        }

        return $this->render('pages/menu/menu.html.twig', array('foods' => $foods));
    }
}