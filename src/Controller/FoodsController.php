<?php


namespace App\Controller;


use App\Entity\Food;
use App\Entity\Type;
use App\Entity\Tag;
use App\Repository\FoodRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Extra\CssInliner\CssInlinerExtension;

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

//    /**
//     * @param Request $request
//     * @return Response
//     * @Route("/foods/{id}/editTags", methods={"PATCH"})
//     */
//    public function editTags(Request $request) : Response {
//        $food = $this->getDoctrine()->getRepository(Food::class)->find($id);
//
//        // získání seznamu typů z databáze
//        $all_tags = $this->getDoctrine()->getRepository(Tag::class)->findAll();
//        $form_tags = null;
//        foreach ($all_tags as $tag) {
//            $form_tags[$tag->getName()] = $tag->getId();
//        }
//
//        $add_tags = new Tag();
//        // Dole to pak použiju k vyhledání toho hráča, co se má uložit
//        // v Tags je připravenna proměnná, do které se pole uloží
//        $form_add_tag = $this->createFormBuilder($add_tags)
//            ->add('tags_array', ChoiceType::class, array(
//                'choices'  => $form_tags,
//                'multiple' => true,
//                'expanded' => true,
//                'attr' => array('class' => 'form-group'),
//                'label' => 'Dostupné tagy' ))
//            ->add('submit', SubmitType::class, array(
//                'label' => 'Uložit',
//                'attr' => array('class' => 'btn btn btn-success mt-3', 'data-dissmiss' => 'modal')) )
//            ->getForm();
//
//        // Zpracování add formuláře.
//        $form_add_tag->handleRequest($request);
//        if ($form_add_tag->isSubmitted() && $form_add_tag->isValid()) {
//            foreach ($food->getTags() as $tag) {
//                $food->removeTag($tag);
//            }
//
//            foreach ($add_tags->getTagsArray() as $tag) {
//                $food->addTag($tag);
//            }
////            // sic je tady getName, tak do name jsem výše uložil ID toho hráča, takže se hledá podle ID, sorry.
////            $team->addPlayer($player);
////            $this->getDoctrine()->getManager()->persist($team);
////            $this->getDoctrine()->getManager()->flush();
////            $this->addFlash('success', 'Hráč \'' . $player->getName() . '\' byl úspěšně  do týmu \'' . $team->getName() . '\'.');
////            return $this->redirect($request->getUri());
//        }
//    }

    function array_push_assoc($array, $key, $value){
        $array[$key] = $value;
        return $array;
    }

    private function make_addtag_form (Request $request) {
        $tags = $this->getDoctrine()->getRepository(Tag::class)->findAll();
        $tags_choices = [];
        foreach ($tags as $tag) {
            $tags_choices = $this->array_push_assoc($tags_choices, $tag->getName(),  $tag->getId());
        }

//        <input type="checkbox" class="custom-control-input" id="customCheck1" checked="">
//      <label class="custom-control-label" for="customCheck1">Check this custom checkbox</label>

        $formtags = $this->createFormBuilder()
            ->add('id', NumberType::class, [
                'label' => false,
                'attr' => array('class' => 'd-none')
            ])
            ->add('tags', ChoiceType::class, [
                'label' => false,
                'choices' => $tags_choices,
                'choice_attr' => function($choice, $key, $value) {
                    // adds a class like attending_yes, attending_no, etc
                    return ['class' => 'form-checkbox'];
                },
                'expanded' => true,
                'multiple' => true,
                'attr' => array('class' => 'custom-control custom-checkbox')
            ])
            ->add('submit', SubmitType::class, array(
                'label' => 'Uložit',
                'attr' => array('class' => 'btn btn btn-success mt-3', 'data-dissmiss' => 'modal')) )
            ->getForm();

        // Zpracování editačního formuláře.
        $formtags->handleRequest($request);
        if ($formtags->isSubmitted()) {
            $food_id = $request->request->get("form")["id"];
            $tags_ids = $request->request->get("form")["tags"];
            $food = $this->getDoctrine()->getRepository(Food::class)->find($food_id);
            $tags = [];
            foreach ($tags_ids as $tag_id) {
                $tag = $this->getDoctrine()->getRepository(Tag::class)->find($tag_id);
                array_push($tags, $tag);
            }
            $food->setTags($tags);
          //  exit;
            // TODO handle
            $this->addFlash('notice', 'Tagy byly editovány.');
        }

        return $formtags;
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/foods", methods={"GET", "POST"})
     */
    public function index(Request $request) : Response {
        $food = new Food();
        $formadd = $this->make_me_form($food, $request);
        $formaddtag = $this->make_addtag_form($request);

        // získání seznamu typů z databáze
        $types = $this->getDoctrine()->getRepository(Type::class)->findAll();

        // promněnné pro výpis
        $table['name'] = "foods";
        $table['headers'] = array("Název", "Popis", "Cena", "Tagy", "Typ");

        // získání seznamu jídel
        $foods = $this->getDoctrine()->getRepository(Food::class)->findAll();

        return $this->render('pages/foods/foods.html.twig', array('formadd' => $formadd->createView(),
            //'table' => $table, 'types' => $types, 'tags' => $tags, 'foods' => $foods));
            'table' => $table, 'types' => $types, 'foods' => $foods, 'formAddTag' => $formaddtag->createView()));
    }
}