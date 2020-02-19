<?php


namespace App\Controller;


use App\Entity\Food;
use App\Entity\History;
use App\Entity\Settings;
use App\Entity\Tag;
use App\Entity\Template;
use App\Entity\Type;
use DateTime;
use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HistoryController
 * @package App\Controller
 */
class HistoryController extends AbstractController {
    /**
     * @Route("/history", methods={"GET", "POST"})
     */
    public function index(){
        $history = $this->getDoctrine()->getRepository(History::class)->findAll();
        $prepared_history = null;
        $i = 0;
        foreach ($history as $week) {
            $prepared_history[$i]['date'] = $week->getDateFromFormatted();
            $prepared_history[$i]['url'] = '/menu?json=' . json_encode($week->getJson());
        }
        return $this->render('pages/history/history.html.twig', array('history' => $prepared_history));
    }

    /**
     * @Route("/history/add", methods={"GET", "POST"})
     */
    public function add() {
        // TODO dod2lat a6 bude klit. :)
        $json = file_get_contents('php://input');
        $data = json_decode ($json);
        dump($json);
        dump($data);
        $history = new History();
        $history->setDateFrom($data->date);
        $date = new DateTime();
        dump($data->date);
        dump($data->json);
        dump(json_encode($data->json));
        $history->setJson(json_encode($data->json));
        dump($history);

        return new Response();
    }
}