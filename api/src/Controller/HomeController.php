<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SportRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends BaseController
{
    #[Route('/', name: 'app_home')]
    public function index(SportRepository $sportRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'sports' => $sportRepository->findActiveSortedBy('name', 'ASC')
        ]);
    }
}
