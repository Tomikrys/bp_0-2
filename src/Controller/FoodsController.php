<?php


namespace App\Controller;


use App\Entity\Food;
use App\Entity\Type;
use App\Repository\FoodRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
     * @param $id
     * @Route("/foods/delete/{id}", methods={"DELETE"})
     */
    public function delete(Request $request, $id) {
        $food = $this->getDoctrine()->getRepository(Food::class)->find($id);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($food);
        $entityManager->flush();
        $response = new Response();
        $response->send();
    }

    /**
     * @param $data
     * @param Food $food
     */
    private function fillUpFood($data, Food $food){
        $food->setName($data->name);
        $food->setDescription($data->description);
        $food->setprice($data->price);
        // TODO
        $entityManager = $this->getDoctrine()->getManager();
        $food->setTypeByString($data->type, $entityManager);
    }

    /**
     * @param Request $request
     * @Route("/foods/edit", methods={"PATCH"})
     * @return Response
     */
    public function editTableFood(Request $request) {
        $json = file_get_contents('php://input');
        $data = json_decode ($json);

        $id =  $data->id;
        $entityManager = $this->getDoctrine()->getManager();
        $food = $entityManager->getRepository(Food::class)->find($id);

        if (!$food) {
            throw $this->createNotFoundException(
                'Nenalezeno jídlo pro id '.$id
            );
        }

        $this->fillUpFood($data, $food);
        $entityManager->flush();

        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @Route("/foods/add", methods={"POST"})
     */
    public function addTableFood(Request $request) {
        $json = file_get_contents('php://input');
        $data = json_decode ($json);

        $food = new Food();
        $entityManager = $this->getDoctrine()->getManager();

        $this->fillUpFood($data, $food);
        $this->foodRepository->save($food);
        $entityManager->flush();

        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/foods", methods={"GET", "POST"})
     */
    public function index(Request $request) : Response {

        $food = new Food();
        $formadd = $this->make_me_form($food, $request);


        // získání seznamu typů z databáze
        $types = $this->getDoctrine()->getRepository(Type::class)->findAll();

        // promněnné pro výpis
        $table['name'] = "foods";
        $table['headers'] = array("Název", "Popis", "Cena", "Tagy", "Typ");

        // získání seznamu jídel
        $foods = $this->getDoctrine()->getRepository(Food::class)->findAll();


        return $this->render('pages/foods/foods.html.twig', array('formadd' => $formadd->createView(), 'table' => $table, 'types' => $types, 'foods' => $foods));
    }
}