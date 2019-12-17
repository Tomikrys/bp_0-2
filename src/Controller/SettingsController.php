<?php


namespace App\Controller;


use App\Entity\Settings;
use App\Entity\Tag;
use App\Entity\Type;
use http\Env\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MenuController
 * @package App\Controller
 */
class SettingsController extends AbstractController {

    public function default_settings() {
        $settings = new Settings();
        $days = ["Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek"];
        $meals = ["Polévka", "Masové", "Vegetariánské"];
        $settings->setDays($days);
        $settings->setMeals($meals);
        $this->getDoctrine()->getManager()->persist($settings);
        $this->getDoctrine()->getManager()->flush();

        $types = ["polévka", "jídlo"];
        foreach ($types as $type) {
            $new_type = new Type();
            $new_type->setName($type);
            $this->getDoctrine()->getManager()->persist($new_type);
            $this->getDoctrine()->getManager()->flush();
        }

        $tags = ["vege", "spicy", "beef", "lamb", "shrink"];
        foreach ($tags as $tag) {
            $new_tag = new Tag();
            $new_tag->setName($tag);
            $this->getDoctrine()->getManager()->persist($new_tag);
            $this->getDoctrine()->getManager()->flush();
        }
    }

    /**
     * @Route("/settings/initialize", methods={"GET", "POST"})
     */
    public function  initialize() {
        $this->default_settings();
        return new Response();
    }

    /**
     * @Route("/settings", methods={"GET", "POST"})
     */
    public function index(){
        // naplnění struktury pro výpis tabulky
        $settings = $this->getDoctrine()->getRepository(Settings::class)->find(1);
        $types = $this->getDoctrine()->getRepository(Type::class)->findAll();
        $tags = $this->getDoctrine()->getRepository(Tag::class)->findAll();

        return $this->render('pages/settings/settings.html.twig', array('settings' => $settings, 'types' => $types, 'tags' => $tags));
    }

}