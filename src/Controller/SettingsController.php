<?php


namespace App\Controller;


use App\Entity\Settings;
use App\Entity\Tag;
use App\Entity\Template;
use App\Entity\Type;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MenuController
 * @package App\Controller
 */
class SettingsController extends AbstractController {

    public function default_settings() {
        $settings = new Settings();
        $days = [["Pondělí", "Monday"], ["Úterý", "Tuesday"], ["Středa", "Wednesday"], ["Čtvrtek", "Thursday"], ["Pátek", "Friday"]];
        $meals = ["Polévka", "Masové", "Vegetariánské"];
        $settings->setDays($days);
        $settings->setMeals($meals);
        $this->getDoctrine()->getManager()->persist($settings);
        $this->getDoctrine()->getManager()->flush();

//        $types = ["polévka", "jídlo"];
//        foreach ($types as $type) {
//            $new_type = new Type();
//            $new_type->setName($type);
//            $this->getDoctrine()->getManager()->persist($new_type);
//            $this->getDoctrine()->getManager()->flush();
//        }
//
//        $tags = ["vege", "spicy", "beef", "lamb", "shrink"];
//        foreach ($tags as $tag) {
//            $new_tag = new Tag();
//            $new_tag->setName($tag);
//            $this->getDoctrine()->getManager()->persist($new_tag);
//            $this->getDoctrine()->getManager()->flush();
//        }
    }

    /**
     * @Route("/settings/initialize", methods={"GET", "POST"})
     */
    public function  initialize() {
        $this->default_settings();
    }

    /**
     * @Route("/settings", methods={"GET", "POST"})
     */
    public function index(){
        // naplnění struktury pro výpis tabulky
        $settings = $this->getDoctrine()->getRepository(Settings::class)->find(1);
        $types = $this->getDoctrine()->getRepository(Type::class)->findAll();
        $tags = $this->getDoctrine()->getRepository(Tag::class)->findAll();
        $templates = $this->getDoctrine()->getRepository(Template::class)->findAll();
//        dump($templates);
//        exit;


        return $this->render('pages/settings/settings.html.twig', array('settings' => $settings, 'types' => $types, 'tags' => $tags, 'templates' => $templates));
    }

    /**
     * @return Response
     * @Route("/settings/save/days", methods={"GET", "POST"})
     */
    public function save_days() {
        // TODO nemuye byt 2
        $settings = $this->getDoctrine()->getRepository(Settings::class)->find(1);
        $json = file_get_contents('php://input');
        $days = json_decode ($json);
        $settings->setDays($days);
        $this->getDoctrine()->getManager()->persist($settings);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'Dny byly upraveny.');
        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @return Response
     * @Route("/settings/save/meals", methods={"GET", "POST"})
     */
    public function save_meals() {
        // TODO nemuye byt 2
        $settings = $this->getDoctrine()->getRepository(Settings::class)->find(1);
        $json = file_get_contents('php://input');
        $meals = json_decode ($json);
        $settings->setMeals($meals);
        $this->getDoctrine()->getManager()->persist($settings);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'Jídla byla upravena.');
        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/delete/type/{id}", methods={"GET", "POST", "DELETE"})
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function delete_type(Request $request, $id) {
        $type = $this->getDoctrine()->getRepository(Type::class)->find($id);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($type);
        $entityManager->flush();
        $this->addFlash('warning', 'Typ byl smazán.');
        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/delete/template/{id}", methods={"GET", "POST", "DELETE"})
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function delete_template(Request $request, $id) {
        $template = $this->getDoctrine()->getRepository(Template::class)->find($id);
        $entityManager = $this->getDoctrine()->getManager();
        $filesystem = new Filesystem();
        //$filesystem->remove(['symlink', '/path/to/directory', 'activity.log']);
        $filesystem->remove($template->getRealPath());
        $entityManager->remove($template);
        $entityManager->flush();
        $this->addFlash('warning', 'Šablona byla smazán.');
        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/delete/tag/{id}", methods={"GET", "POST", "DELETE"})
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function delete_tag(Request $request, $id) {
        $type = $this->getDoctrine()->getRepository(Tag::class)->find($id);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($type);
        $entityManager->flush();
        $this->addFlash('warning', 'Tag byl smazán.');
        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/edit/tag", methods={"GET", "POST"})
     */
    public function edit_tag() {
        $json = file_get_contents('php://input');
        $data = json_decode ($json);

        $id =  $data->id;
        $entityManager = $this->getDoctrine()->getManager();
        $tag = $entityManager->getRepository(Tag::class)->find($id);

        if (!$tag) {
            $tag = new Tag();
        }

        $tag->setName($data->name);

        $this->getDoctrine()->getManager()->persist($tag);
        $entityManager->flush();
        $this->addFlash('success', 'Tag byl upraven.');

        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/edit/template", methods={"GET", "POST"})
     */
    public function edit_template() {
        $json = file_get_contents('php://input');
        $data = json_decode($json);

        $id =  $data->id;
        $entityManager = $this->getDoctrine()->getManager();
        $template = $entityManager->getRepository(Template::class)->find($id);

        if (!$template) {
            $template = new Template();
        }

        $template->setName($data->name);

        $this->getDoctrine()->getManager()->persist($template);
        $entityManager->flush();
        $this->addFlash('success', 'Šablona byla upravena.');

        $response = new Response();
        $response->send();
        return $response;
    }

    /**
     * @Route("/settings/edit/type", methods={"GET", "POST"})
     */
    public function edit_type() {
        $json = file_get_contents('php://input');
        $data = json_decode ($json);

        $id =  $data->id;
        $entityManager = $this->getDoctrine()->getManager();
        $type = $entityManager->getRepository(Type::class)->find($id);

        if (!$type) {
            $type = new Type();
        }

        $type->setName($data->name);

        $this->getDoctrine()->getManager()->persist($type);
        $entityManager->flush();
        $this->addFlash('success', 'Type byl upraven.');

        $response = new Response();
        $response->send();
        return $response;
    }

}