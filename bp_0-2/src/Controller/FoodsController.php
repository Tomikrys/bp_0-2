<?php


namespace App\Controller;


use App\Entity\Food;
use App\Entity\Type;
use App\Repository\FoodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class FoodsController
 * @package App\Controller
 */
class FoodsController extends AbstractController {
    /**
     * @var FoodRepository
     */
    private $foodRepository;

    /**
     * FoodsController constructor.
     * @param FoodRepository $foodRepository
     */
    public function __construct(FoodRepository $foodRepository)
    {
        $this->foodRepository = $foodRepository;
    }

    private function make_me_form ($food, Request $request) {
        // Vytváření editačního formuláře podle entity článku.
        $formadd = $this->createFormBuilder($food)
            ->add('name', TextType::class, array( 'attr'=> array(
                'class' => 'form-control',
                'label' => 'Název jídla') ))
            ->add('description', TextType::class, array( 'attr'=> array(
                'class' => 'form-control',
                'label' => 'Popis') ))
            ->add('price', NumberType::class, array( 'attr'=> array(
                'class' => 'form-control',
                'label' => 'Cena') ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Přidat jídlo',
                'attr' => array('class' => 'btn btn btn-success mt-3', 'data-dissmiss' => 'modal')) )
            ->getForm();

        // Zpracování editačního formuláře.
        $formadd->handleRequest($request);
        if ($formadd->isSubmitted() && $formadd->isValid()) {
            $this->foodRepository->save($food);
            $this->addFlash('notice', 'Článek byl úspěšně uložen.');
        }

        return $formadd;
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/foods", methods={"GET", "POST"})
     */
    public function index(Request $request) : Response {

        $food = new Food();
        $formadd = $this->make_me_form($food, $request);


        // získání seznamu jídel
        $foods = $this->getDoctrine()->getRepository(Food::class)->findAll();
        // získání seznamu typů z databáze
        $types = $this->getDoctrine()->getRepository(Type::class)->findAll();


        return $this->render('pages/foods/foods.html.twig', array('addforminline' => $formadd->createView(), 'foods' => $foods, 'types' => $types));
    }
}