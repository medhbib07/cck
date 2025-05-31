<?php

namespace App\Controller;
use App\Repository\UniversiteRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function homePage(): Response
    {
        return $this->render('user/base.html.twig', [
            // 'controller_name' => 'HomeController',
        ]);
    }
      #[Route('/view-universities', name: 'app_view_universities')]
    public function viewUniversities(UniversiteRepository $universiteRepository): Response
    {
        $universites = $universiteRepository->findAllWithEtablissements();
        
        return $this->render('user/view.html.twig', [
            'universites' => $universites,
        ]);
    }
}

