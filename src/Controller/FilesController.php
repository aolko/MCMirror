<?php declare(strict_types=1);

namespace App\Controller;

use App\Service\ApplicationService;
use App\Service\BuildsService;
use App\Service\DownloadCounterService;
use App\Structs\LatestBuild;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

class FilesController extends AbstractController
{
    /**
     * @var ApplicationService
     */
    private $applicationService;
    /**
     * @var BuildsService
     */
    private $buildsService;
    /**
     * @var DownloadCounterService
     */
    private $downloadCounter;

    /**
     * FilesController constructor.
     *
     * @param ApplicationService     $applicationService
     * @param BuildsService          $buildsService
     * @param DownloadCounterService $downloadCounter
     */
    public function __construct(ApplicationService $applicationService, BuildsService $buildsService, DownloadCounterService $downloadCounter)
    {
        $this->applicationService = $applicationService;
        $this->buildsService = $buildsService;
        $this->downloadCounter = $downloadCounter;
    }

    /**
     * @Route("/files/{applicationName}/{fileName}", name="files")
     *
     * @param string $applicationName
     * @param string $fileName
     *
     * @return Response
     */
    public function index(string $applicationName, string $fileName): Response
    {
        $application = $this->applicationService->getApplication($applicationName);

        if ($application === null) {
            throw $this->createNotFoundException(sprintf('Could not find Application %s', $applicationName));
        }

        if (!$this->buildsService->doesBuildExist($application, $fileName)) {
            throw $this->createNotFoundException(sprintf('Could not find File %s for Application %s', $fileName, $applicationName));
        }

        $build = $this->buildsService->getBuildForApplication($application, $fileName);

        if ($build instanceof LatestBuild) {
            return $this->redirectToRoute('files', [
                'applicationName' => $applicationName,
                'fileName' => $build->getFile()->getFilename()
            ]);
        }

        $this->downloadCounter->increaseCounter($application, $build);

        return $this->file($build->getFile()->getRealPath());
    }
}
