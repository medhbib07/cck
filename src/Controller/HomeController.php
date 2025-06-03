<?php

namespace App\Controller;
use App\Repository\UniversiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Universite;
use App\Entity\Etablissement;
use App\Entity\Etudiant;

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
    public function viewUniversities(UniversiteRepository $universiteRepository, EntityManagerInterface $em): Response
    {
        $totalUniversites = $em->getRepository(Universite::class)->count([]);
$totalEtablissements = $em->getRepository(Etablissement::class)->count([]);
$totalEtudiants = $em->getRepository(Etudiant::class)->count([]);
        $universites = $universiteRepository->findAllWithEtablissements();
        
        return $this->render('user/view.html.twig', [
            'universites' => $universites,
            'totalUniversites' => $totalUniversites,
            'totalEtablissements' => $totalEtablissements,
            'totalEtudiants' => $totalEtudiants,
        ]);
    }
}

