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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extra\CssInliner\CssInlinerExtension;

/**
 * Class DefaultController
 * @package App\Controller
 */
class DefaultController extends AbstractController
{
    /**
     * @Route("/bring_me_back", name="/bring_me_back", methods={"GET", "POST", "DELETE"})
     */
    public function back()
    {
        return new Response(
            "<html>
            <body>
                <script>
                window.history.back();
                </script>
            </body>
        </html>");
    }

    protected function getFlashes()
    {
        if (!$this->container->has('session')) {
            throw new \LogicException('You can not use the addFlash method if sessions are disabled. Enable them in "config/packages/framework.yaml".');
        }

        return $this->container->get('session')->getFlashBag()->peekAll();
    }

    protected function overwriteFlashes(array $flashes)
    {
        $this->container->get('session')->getFlashBag()->setAll($flashes);
    }

    public function manage_flashes()
    {
        $new_flashes = [];
        foreach ($this->getFlashes() as $type => $flashes_of_type) {
            $cleared_flashes = array_count_values($flashes_of_type);
            $cleared_flashes_of_type = [];
            foreach ($cleared_flashes as $flash => $count) {
                if ($count > 1) {
                    $flash .= " počet: " . $count;
                }

                array_push($cleared_flashes_of_type, $flash);
            }
            $new_flashes[$type] = $cleared_flashes_of_type;
        }
        $this->overwriteFlashes($new_flashes);
    }

    /**
     * @Route("/impersonating_user", methods={"GET", "POST"})
     * @param AuthorizationCheckerInterface $authChecker
     * @return RedirectResponse|Response
     */
    public function impersonating_user(AuthorizationCheckerInterface $authChecker)
    {
        $disconnect = $authChecker->isGranted('ROLE_PREVIOUS_ADMIN');
        $user = $_GET['impersonating_user'];
        if ($disconnect) {
            return $this->redirect("/impersonating_user?_switch_user=_exit&impersonating_user=" . $user);
        } else {
            return $this->redirect("/menu?_switch_user=".$user);
        }
    }

    public function get_xml_link() {
        $AWSpath = "https://menickajednodusecz.s3.amazonaws.com";
        $clean_username = $this->getUser()->getCleanUsername();
        return $AWSpath . '/xml/' . $clean_username . "/menu.xml";
    }
}