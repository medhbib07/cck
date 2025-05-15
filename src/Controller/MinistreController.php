<?php

namespace App\Controller;

use App\Entity\Ministre;
use App\Repository\EtudiantRepository;
use App\Repository\EtablissementRepository;
use App\Repository\UniversiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Knp\Snappy\GeneratorInterface;
use League\Csv\Writer;

#[Route('/ministre')]
// #[IsGranted('ROLE_MINISTRE')]
class MinistreController extends AbstractController
{
    public function __construct(
        private EtudiantRepository $etudiantRepository,
        private EtablissementRepository $etablissementRepository,
        private UniversiteRepository $universiteRepository,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    #[Route('/dashboard', name: 'ministre_dashboard', methods: ['GET', 'POST'])]
    public function dashboard(Request $request): Response
    {
        // $user = $this->getUser();
        // if (!$user instanceof Ministre) {
        //     throw $this->createAccessDeniedException('User not authenticated or not a Ministre');
        // }

        $search = $request->query->get('search', '');
        $universite = $request->query->get('universite', '');
        $city = $request->query->get('city', '');
        $etype = $request->query->get('etype', '');
        $page = $request->query->getInt('page', 1);
        $limit = 10;

        // Handle export requests
        if ($request->isMethod('POST')) {
            $exportType = $request->request->get('export_type');
            $tokenId = $exportType === 'pdf' ? 'export_pdf' : 'export_csv';
            $token = new CsrfToken($tokenId, $request->request->get('_token'));

            if (!$this->csrfTokenManager->isTokenValid($token)) {
                $this->addFlash('error', 'Invalid CSRF token.');
                return $this->redirectToRoute('ministre_dashboard');
            }

            $etablissements = $this->etablissementRepository->findWithFilters($search, $universite, $city, $etype);

            // if ($exportType === 'pdf') {
            //     $html = $this->renderView('Backoffice/ministre/pdf_export.html.twig', [
            //         'etablissements' => $etablissements,
            //     ]);

            //     return new StreamedResponse(function() use ($html) {
            //         echo $this->pdfGenerator->getOutputFromHtml($html);
            //     }, 200, [
            //         'Content-Type' => 'application/pdf',
            //         'Content-Disposition' => 'attachment; filename="etablissements_export.pdf"',
            //     ]);
            // }

            if ($exportType === 'csv') {
                $csv = Writer::createFromString();
                $csv->insertOne(['Nom', 'Type', 'Universite', 'Ville', 'Capacite', 'Telephone']);
                foreach ($etablissements as $etablissement) {
                    $csv->insertOne([
                        $etablissement->getNom(),
                        $etablissement->getEtype(),
                        $etablissement->getGroupe() ? $etablissement->getGroupe()->getNom() : 'N/A',
                        $etablissement->getVille(),
                        $etablissement->getCapacite() ?? 'N/A',
                        $etablissement->getTelephone() ?? 'N/A',
                    ]);
                }

                return new StreamedResponse(function() use ($csv) {
                    echo $csv->toString();
                }, 200, [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="etablissements_export.csv"',
                ]);
            }
        }

        // Fetch data for display
        $etablissements = $this->etablissementRepository->findWithFiltersAndPagination(
            $search,
            $universite,
            $city,
            $etype,
            $page,
            $limit
        );
        $total = $this->etablissementRepository->countWithFilters($search, $universite, $city, $etype);

        // Stats
        $stats = [
            'totalUniversites' => $this->universiteRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->getQuery()
                ->getSingleScalarResult(),
            'totalEtablissements' => $this->etablissementRepository->createQueryBuilder('e')
                ->select('COUNT(e.id)')
                ->getQuery()
                ->getSingleScalarResult(),
            'totalStudents' => $this->etudiantRepository->createQueryBuilder('e')
                ->select('COUNT(e.id)')
                ->getQuery()
                ->getSingleScalarResult(),
            'averageScore' => $this->etudiantRepository->createQueryBuilder('e')
                ->select('AVG(e.score)')
                ->where('e.score IS NOT NULL')
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        // Chart data
        $chartData = [
            'universite_data' => $this->etudiantRepository->createQueryBuilder('s')
                ->select('u.nom, COUNT(s.id) as count')
                ->join('s.etablissement', 'e')
                ->join('e.groupe', 'u')
                ->groupBy('u.id')
                ->getQuery()
                ->getResult(),
            'etype_data' => $this->etablissementRepository->createQueryBuilder('e')
                ->select('e.etype, COUNT(e.id) as count')
                ->groupBy('e.etype')
                ->getQuery()
                ->getResult(),
            'score_data' => $this->etudiantRepository->createQueryBuilder('s')
                ->select("
                    CASE
                        WHEN s.score < 5 THEN '0-5'
                        WHEN s.score >= 5 AND s.score < 10 THEN '5-10'
                        WHEN s.score >= 10 AND s.score < 15 THEN '10-15'
                        WHEN s.score >= 15 AND s.score <= 20 THEN '15-20'
                        ELSE 'Unknown'
                    END as range,
                    COUNT(s.id) as count
                ")
                ->where('s.score IS NOT NULL')
                ->groupBy('range')
                ->getQuery()
                ->getResult(),
            'section_data' => $this->etudiantRepository->createQueryBuilder('s')
                ->select('s.section, COUNT(s.id) as count')
                ->groupBy('s.section')
                ->getQuery()
                ->getResult(),
        ];

        // Age data (computed in PHP to avoid DQL issues)
        $students = $this->etudiantRepository->findAll();
        $ageData = ['<18' => 0, '18-25' => 0, '26-35' => 0, '>35' => 0];
        foreach ($students as $student) {
            $age = (new \DateTime())->diff($student->getDateNaissance())->y;
            $range = $age < 18 ? '<18' : ($age <= 25 ? '18-25' : ($age <= 35 ? '26-35' : '>35'));
            $ageData[$range]++;
        }
        $chartData['age_data'] = array_map(fn($range, $count) => ['range' => $range, 'count' => $count], array_keys($ageData), $ageData);

        // Filter options
        $universites = $this->universiteRepository->findAll();
        $cities = $this->etablissementRepository->createQueryBuilder('e')
            ->select('DISTINCT e.ville')
            ->orderBy('e.ville', 'ASC')
            ->getQuery()
            ->getResult();

  // Fetch universities for table
    $universities = $this->universiteRepository->findWithFiltersAndPagination(
        $search,
        $city,
        $etype,
        $page,
        $limit
    );
    $totalUniversities = $this->universiteRepository->countWithFilters($search, $city, $etype);

    return $this->render('Backoffice/ministre/dashboard.html.twig', [
        'etablissements' => $etablissements,
        'total' => $total,
        'universities' => $universities,
        'totalUniversities' => $totalUniversities,
        'page' => $page,
        'limit' => $limit,
        'search' => $search,
        'universite' => $universite,
        'city' => $city,
        'etype' => $etype,
        'stats' => $stats,
        'chartData' => $chartData,
        'universites' => $universites,
        'cities' => array_column($cities, 'ville'),
    ]);
    }
}