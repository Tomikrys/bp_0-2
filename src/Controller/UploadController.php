<?php

namespace App\Controller;

use App\Entity\Template;
use App\Repository\TemplateRepository;
use App\Service\FileUploader;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UploadController extends AbstractController {


    /**
     * @var TemplateRepository
     */
    private $templateRepository;

    /**
     * FoodsController constructor.
     * @param TemplateRepository $templateRepository
     */
    public function __construct(TemplateRepository $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    /**
     * @Route("/doUpload", name="upload")
     * @param Request $request
     * @param string $uploadDir
     * @param FileUploader $uploader
     * @param LoggerInterface $logger
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function index(Request $request, string $uploadDir, FileUploader $uploader, LoggerInterface $logger) {
        $token = $request->get("token");

        if (!$this->isCsrfTokenValid('upload', $token))
        {
            $logger->info("CSRF failure");

            $this->addFlash('error', 'Nepovolená operace.');
//            return new Response("Operation not allowed",  Response::HTTP_BAD_REQUEST,
//                ['content-type' => 'text/plain']);
        }

        $file = $request->files->get('myfile');

        if (empty($file))
        {
            $this->addFlash('error', 'Nebyl zadán soubor.');
//            return new Response("No file specified",
//                Response::HTTP_UNPROCESSABLE_ENTITY, ['content-type' => 'text/plain']);
        }


        $file_fullname = $file->getClientOriginalName();
        $url = $uploadDir . "/" . $file_fullname;

        $filename = substr($file_fullname, 0, strrpos($file_fullname, '.'));
        $extension = substr($file_fullname, strrpos($file_fullname, '.'));
        $i = 0;
        while (file_exists($url)) {
            $i++;
            $filename_dubler = $filename . "-" . $i . $extension;
            $url = $uploadDir . "/" . $filename_dubler;
        }

        if ($i) {
            $filename .= "-" . $i . $extension;
        } else {
            $filename .= $extension;
        }

        $uploader->upload($uploadDir, $file, $filename);
        $templatename = $request->request->get('template_name');
        $template = new Template();

        $template->setPath(basename($url));

        $i = 0;
        $templatename_dubler = $templatename;
        while ($this->getDoctrine()->getRepository(Template::class)->findBy(array('name' => $templatename_dubler))) {
            $i++;
            $templatename_dubler = $templatename . "-" . $i;
        }
        if ($i) {
            $templatename .= "-". $i;
        }
        $template->setName($templatename);

        $this->templateRepository->save($template);

        $this->addFlash('success', 'Šablona byla nahrána.');
//        return new Response("File uploaded",  Response::HTTP_OK,
//            ['content-type' => 'text/plain']);
        return $this->redirect($this->generateUrl('/settings'));
    }
}
