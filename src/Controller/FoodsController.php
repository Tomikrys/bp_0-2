<?php


namespace App\Controller;


use App\Entity\Food;
use App\Entity\Type;
use App\Entity\Tag;
use App\Repository\FoodRepository;
use App\Repository\TagRepository;
use App\Repository\TypeRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Extra\CssInliner\CssInlinerExtension;

/**
 * Class FoodsController
 * @package App\Controller
 */
class FoodsController extends DefaultController {
    /**
     * @var FoodRepository
     */
    private $foodRepository;

    /**
     * @var TagRepository
     */
    private $tagRepository;

    /**
     * @var TypeRepository
     */
    private $typeRepository;

    /**
     * FoodsController constructor.
     * @param FoodRepository $foodRepository
     * @param TagRepository $tagRepository
     * @param TypeRepository $typeRepository
     */
    public function __construct(FoodRepository $foodRepository, TagRepository $tagRepository, TypeRepository $typeRepository)
    {
        $this->foodRepository = $foodRepository;
        $this->tagRepository = $tagRepository;
        $this->typeRepository = $typeRepository;
    }

    /**
     * @param $food
     * @param Request $request
     * @return FormInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function make_me_form ($food, Request $request) {
        // Vytváření editačního formuláře podle entity článku.
        $formadd = $this->createFormBuilder($food)
            ->add('name', TextType::class, [ 'attr'=> [
                'class' => 'form-control',
                'label' => 'Název jídla'] ])
            ->add('description', TextType::class, [ 'attr'=> [
                'class' => 'form-control',
                'label' => 'Popis'] ])
            ->add('price', NumberType::class, [ 'attr'=> [
                'class' => 'form-control',
                'label' => 'Cena'] ])
            ->add('submit', SubmitType::class, [
                'label' => 'Přidat jídlo',
                'attr' => ['class' => 'btn btn btn-success mt-3', 'data-dissmiss' => 'modal']] )
            ->getForm();

        // Zpracování editačního formuláře.
        $formadd->handleRequest($request);
        if ($formadd->isSubmitted() && $formadd->isValid()) {
            $this->foodRepository->save($food);
            $this->addFlash('success', 'Jídlo bylo úspěšně přidáno.');
        }

        return $formadd;
    }

    /**
     * @param Request $request
     * @param $id
     * @Route("/foods/delete/{id}", methods={"DELETE"})
     * @return Response
     */
    public function delete(Request $request, $id) {
        $user = $this->getUser();
        $food = $this->getDoctrine()->getRepository(Food::class)->findOneBy(['user' => $user, 'id' => $id]);
        if (!$food) {
            $this->addFlash('error', 'Jídlo neexistuje.');
            throw $this->createNotFoundException('Nenalezeno jídlo pro id '.$id);
        }
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($food);
        $entityManager->flush();
        $this->addFlash('warning', 'Jídlo bylo smazáno.');
        $response = new Response();
        return $response;
    }

    /**
     * @param $data
     * @param Food $food
     */
    private function fillUpFood($data, Food $food){
        $food->setName($data->name);
        $food->setDescription($data->description);
        $food->setprice($data->price);
        $entityManager = $this->getDoctrine()->getManager();
        $food->setTypeById($data->type, $entityManager);
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
        $user = $this->getUser();
        $food = $this->getDoctrine()->getRepository(Food::class)->findOneBy(['user' => $user, 'id' => $id]);
        if (!$food) {
            $this->addFlash('error', 'Jídlo neexistuje.');
            throw $this->createNotFoundException('Nenalezeno jídlo pro id '.$id);
        }

        $this->fillUpFood($data, $food);
        $entityManager->flush();
        $this->addFlash('warning', 'Jídlo bylo upraveno.');

        $response = new Response();
        $response->send();
        return $response;
    }


    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @Route("foods/edit-tags", methods={"PATCH"})
     */
    public function editTableFoodTags(Request $request) {
        $json = file_get_contents('php://input');
        $data = json_decode ($json);

        $id =  $data->id;
        $add_tags_ids = $data->add_tags;
        $remove_other_tags_ids = $data->remove_other_tags;
        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $food = $this->getDoctrine()->getRepository(Food::class)->findOneBy(['user' => $user, 'id' => $id]);
        if (!$food) {
            $this->addFlash('error', 'Jídlo neexistuje.');
            throw $this->createNotFoundException('Nenalezeno jídlo pro id '.$id);
        }

        if (!$food) {
            throw $this->createNotFoundException(
                'Nenalezeno jídlo pro id '.$id
            );
        }

        $user = $this->getUser();
        $tags = $entityManager->getRepository(Tag::class)->findBy(['user' => $user]);

        $remove_other_tags = [];
        foreach ($remove_other_tags_ids as $id) {
            $tag = $entityManager->getRepository(Tag::class)->findOneBy(['user' => $user, 'id' => $id]);
            array_push($remove_other_tags, $tag);
        }

        $remove_tags = array_udiff($tags, $remove_other_tags,
            function ($obj_a, $obj_b) {
                return $obj_a->getId() - $obj_b->getId();
            }
        );

        $add_tags = [];
        foreach ($add_tags_ids as $id) {
            $tag = $entityManager->getRepository(Tag::class)->findOneBy(['user' => $user, 'id' => $id]);
            array_push($add_tags, $tag);
        }

        foreach ($remove_tags as $remove_tag) {
            $food->removeTag($remove_tag);
        }

        foreach ($add_tags as $add_tag) {
            $food->addTag($add_tag);
        }

        $this->foodRepository->save($food);
        $this->addFlash('warning', 'Tagy byly upraveny.');

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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $json = file_get_contents('php://input');
        $data = json_decode ($json);

        $food = new Food();
        $entityManager = $this->getDoctrine()->getManager();

        $this->fillUpFood($data, $food);
        $food->setUser($this->getUser());
        $this->foodRepository->save($food);
        $this->addFlash('success', 'Jídlo bylo přidáno.');

        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @param $array
     * @param $key
     * @param $value
     * @return mixed
     */
    function array_push_assoc($array, $key, $value){
        $array[$key] = $value;
        return $array;
    }

    /**
     * @return FormInterface
     */
    function make_edittags_on_multiple_foods_form(){
        $user = $this->getUser();
        $tags = $this->getDoctrine()->getRepository(Tag::class)->findBy(['user' => $user]);
        $tags_choices = [];
        foreach ($tags as $tag) {
            $tags_choices = $this->array_push_assoc($tags_choices, $tag->getName(),  $tag->getId());
        }

        //        <input type="checkbox" class="custom-control-input" id="customCheck1" checked="">
        //      <label class="custom-control-label" for="customCheck1">Check this custom checkbox</label>

        return $this->createFormBuilder()
            ->add('id', NumberType::class, [
                'label' => false,
                'attr' => ['class' => 'd-none']
            ])
            ->add('tags', ChoiceType::class, [
                'label' => false,
                'choices' => $tags_choices,
                'choice_attr' => function($choice, $key, $value) {
                    // adds a class like attending_yes, attending_no, etc
                    return ['class' => 'form-checkbox add_tag_modal_checkbox'];
                },
                'expanded' => true,
                'multiple' => true,
                'attr' => ['class' => 'custom-control custom-checkbox edit_multiple_foods_tag_form']
            ])
            ->getForm();
    }

    private function make_addtag_form (Request $request) {
        $user = $this->getUser();
        $tags = $this->getDoctrine()->getRepository(Tag::class)->findBy(['user' => $user]);;
        $tags_choices = [];
        foreach ($tags as $tag) {
            $tags_choices = $this->array_push_assoc($tags_choices, $tag->getName(),  $tag->getId());
        }

//        <input type="checkbox" class="custom-control-input" id="customCheck1" checked="">
//      <label class="custom-control-label" for="customCheck1">Check this custom checkbox</label>

        $formtags = $this->createFormBuilder()
            ->add('id', NumberType::class, [
                'label' => false,
                'attr' => ['class' => 'd-none']
            ])
            ->add('tags', ChoiceType::class, [
                'label' => false,
                'choices' => $tags_choices,
                'choice_attr' => function($choice, $key, $value) {
                    // adds a class like attending_yes, attending_no, etc
                    return ['class' => 'form-checkbox add_tag_modal_checkbox'];
                },
                'expanded' => true,
                'multiple' => true,
                'attr' => ['class' => 'custom-control custom-checkbox']
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Uložit',
                'attr' => ['class' => 'btn btn btn-success add_tag_modal_button', 'data-dissmiss' => 'modal']] )
            ->getForm();

        // Zpracování editačního formuláře.
        $formtags->handleRequest($request);
        if ($formtags->isSubmitted()) {
            $food_id = $request->request->get("form")["id"];
            $request_form = $request->request->get("form");
            $tags_ids = [];
            if (array_key_exists("tags",$request_form)) {
                $tags_ids = $request_form["tags"];
            }
            $food = $this->getDoctrine()->getRepository(Food::class)->find($food_id);
            $tags = [];
            foreach ($tags_ids as $tag_id) {
                $tag = $this->getDoctrine()->getRepository(Tag::class)->find($tag_id);
                array_push($tags, $tag);
            }
            if ($food) {
                $food->setTags($tags);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->flush();
            } else {
                $this->addFlash('error', 'Jídlo pro úpravu tagů nebylo nalezeno.');
            }
            $this->addFlash('warning', 'Tagy byly editovány.');
        }

        return $formtags;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @Route("/foods", methods={"GET", "POST"})
     */
    public function index(Request $request) : Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $food = new Food();
        $formadd = $this->make_me_form($food, $request);
        $formaddtag = $this->make_addtag_form($request);
        $formeditfoodtag = $this->make_edittags_on_multiple_foods_form($request);

        // získání seznamu typů z databáze
        $types = $this->getDoctrine()->getRepository(Type::class)->findBy(['user' => $user]);

        // promněnné pro výpis
        $table['name'] = "foods";
        $table['headers'] =
            [
                ["name"=>"Název", "class"=>""],
                ["name"=>"Popis", "class"=>""],
                ["name"=>"Cena", "class"=>""],
                ["name"=>"Tagy", "class"=>"sorttable_nosort"],
                ["name"=>"Typ", "class"=>"sorttable_nosort"]
            ];

        // získání seznamu jídel
        $foods = $this->getDoctrine()->getRepository(Food::class)->findBy(['user' => $user]);

        $tags = $this->getDoctrine()->getRepository(Tag::class)->findBy(['user' => $user]);

        $this->manage_flashes();
        return $this->render('pages/foods/foods.html.twig', ['formadd' => $formadd->createView(),
            'table' => $table, 'types' => $types, 'foods' => $foods, 'tags' => $tags,
            'formAddTag' => $formaddtag->createView(), 'editFoodFormAddTag' => $formeditfoodtag->createView(),
            'editFoodFormRemoveTag' => $formeditfoodtag->createView()]);
    }

    /**
     * @Route("/import", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function import_csv(Request $request) : Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $entityManager = $this->getDoctrine()->getManager();
        // Název|Popis|Cena|Typ|Tag|Tag
        $import = $request->getContent();

        // rozdeleni na radky
        $line_separator = "\r\n";
        $line = strtok($import, $line_separator);
        while ($line !== false) {

            $food = new Food();
            $food->setUser($user);

            // rozdeleni na zaznamy
            $arr = explode('|', $line);
            foreach ($arr as $i => $word) {
                switch ($i) {
                    //nazev
                    case 0:
                        $food->setName($word);
                        break;
                    //popis
                    case 1:
                        $food->setDescription($word);
                        break;
                    //cena
                    case 2:
                        $food->setPrice($word);
                        break;
                    //typ
                    case 3:
                        $type = $this->getDoctrine()->getRepository(Type::class)->findOneBy(['user' => $user, 'name' => $word]);
                        if ($type == []) {
                            $type = new Type();
                            $type->setName($word);
                            $type->setUser($this->getUser());
                            $this->typeRepository->save($type);
                            $type = $this->getDoctrine()->getRepository(Type::class)->findOneBy(['user' => $user, 'name' => $word]);
                        }
                        $food->setType($type);
                        break;
                    //tagy
                    default:
                        $tag = $this->getDoctrine()->getRepository(Tag::class)->findOneBy(['user' => $user, 'name' => $word]);
                        if ($tag == []) {
                            $tag = new Tag();
                            $tag->setName($word);
                            $tag->setUser($this->getUser());
                            $this->tagRepository->save($tag);
                            $tag = $this->getDoctrine()->getRepository(Tag::class)->findOneBy(['user' => $user, 'name' => $word]);
                        }
                        $food->addTag($tag);
                        break;
                }
            }
            //dump($food);
            $this->foodRepository->save($food);
            $entityManager->flush();
            //dump($line);

            $line = strtok($line_separator);
        }
        return new Response();
    }
}