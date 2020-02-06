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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Extra\CssInliner\CssInlinerExtension;

/**
 * Class DefaultController
 * @package App\Controller
 */
class DefaultController extends AbstractController {
    /**
     * @Route("/bring_me_back", name="/bring_me_back", methods={"GET", "POST", "DELETE"})
     */
    public function back() {
        return new Response(
            "<html>
            <body>
                <script>
                window.history.back();
                </script>
            </body>
        </html>");
    }

    /**
     * @Route("/testaws", name="/bring_me_back", methods={"GET", "POST", "DELETE"})
     */
    public function testaws() {
        return $this->render('testaws.html.twig');
    }
}