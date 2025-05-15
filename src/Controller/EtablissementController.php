<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\Etablissement;
use App\Repository\EtudiantRepository;
use App\Repository\EtablissementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

#[Route('/etablissement')]
class EtablissementController extends AbstractController
{
    private $entityManager;
    private $etudiantRepository;
    private $etablissementRepository;
    private $validator;
    private $csrfTokenManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        EtudiantRepository $etudiantRepository,
        EtablissementRepository $etablissementRepository,
        ValidatorInterface $validator,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->entityManager = $entityManager;
        $this->etudiantRepository = $etudiantRepository;
        $this->etablissementRepository = $etablissementRepository;
        $this->validator = $validator;
        $this->csrfTokenManager = $csrfTokenManager;
    }

#[Route('/dashboard', name: 'etablissement_dashboard', methods: ['GET'])]
public function dashboard(Request $request): Response
{
    $user = $this->getUser();
    if (!$user instanceof Etablissement) {
        throw $this->createAccessDeniedException('User not authenticated or not an Etablissement');
    }

    $search = $request->query->get('search', '');
    $page = $request->query->getInt('page', 1);
    $limit = 5;

    // Fetch students
    $students = $this->etudiantRepository->findByEtablissementWithSearchAndPagination($user, $search, $page, $limit);
    $total = $this->etudiantRepository->countByEtablissementWithSearch($user, $search);

    // Stats
    $stats = [
        'totalStudents' => $this->etudiantRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.etablissement = :etablissement')
            ->setParameter('etablissement', $user)
            ->getQuery()
            ->getSingleScalarResult(),
        'publicEtablissements' => $this->etablissementRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.etype = :etype')
            ->setParameter('etype', Etablissement::ETYPE_PUBLIC)
            ->getQuery()
            ->getSingleScalarResult(),
        'privateEtablissements' => $this->etablissementRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.etype = :etype')
            ->setParameter('etype', Etablissement::ETYPE_PRIVATE)
            ->getQuery()
            ->getSingleScalarResult(),
        'uniqueEtablissements' => 1, // Only the logged-in etablissement
    ];

    // Chart data
    $chartData = [
        'etablissement_data' => $this->etudiantRepository->createQueryBuilder('u')
            ->select('e.nom, COUNT(u.id) as count')
            ->join('u.etablissement', 'e')
            ->where('u.etablissement = :etablissement')
            ->setParameter('etablissement', $user)
            ->groupBy('e.id')
            ->getQuery()
            ->getResult(),

'age_data' => $this->etudiantRepository->createQueryBuilder('u')
    ->select("
        CASE
            WHEN (DATE_DIFF(CURRENT_DATE(), u.dateNaissance) / 365) < 18 THEN '<18'
            WHEN (DATE_DIFF(CURRENT_DATE(), u.dateNaissance) / 365) >= 18 AND (DATE_DIFF(CURRENT_DATE(), u.dateNaissance) / 365) <= 25 THEN '18-25'
            WHEN (DATE_DIFF(CURRENT_DATE(), u.dateNaissance) / 365) >= 26 AND (DATE_DIFF(CURRENT_DATE(), u.dateNaissance) / 365) <= 35 THEN '26-35'
            ELSE '>35'
        END as range,
        COUNT(u.id) as count
    ")
    ->where('u.etablissement = :etablissement')
    ->setParameter('etablissement', $user)
    ->groupBy('range')
    ->getQuery()
    ->getResult(),

    ];

    return $this->render('Backoffice/etablissement/dashboard.html.twig', [
        'students' => $students,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'search' => $search,
        'stats' => $stats,
        'chartData' => $chartData,
        'etablissement' => $user,
    ]);
}


#[Route('/student/new', name: 'etablissement_student_new', methods: ['GET', 'POST'])]
public function newStudent(Request $request): Response
{
    $user = $this->getUser();
    if (!$user instanceof Etablissement) {
        throw $this->createAccessDeniedException('User not authenticated or not an Etablissement');
    }

    if ($request->isMethod('POST')) {
        $data = $request->request->all();
        $token = new CsrfToken('student_create', $data['_token'] ?? '');
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('etablissement_student_new');
        }

        $etudiant = new Etudiant();
        $etudiant->setNom($data['nom'] ?? '');
        $etudiant->setPrenom($data['prenom'] ?? '');
        $etudiant->setEmail($data['email'] ?? '');
        try {
            $etudiant->setDateNaissance(new \DateTime($data['date_naissance'] ?? 'now'));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Invalid date of birth.');
            return $this->redirectToRoute('etablissement_student_new');
        }
        // Auto-generate unique numeroEtudiant
        $etudiant->setNumeroEtudiant('ETU' . time() . rand(1000, 9999));
        $etudiant->setNumCin($data['num_cin'] ?? '');
        $etudiant->setSection($data['section'] ?? '');
        $etudiant->setScore(isset($data['score']) ? (float)$data['score'] : null);
        $etudiant->setNiveau($data['niveau'] ?? '');
        $etudiant->setLocalisation($data['localisation'] ?? '');
        $etudiant->setEtablissement($user);

        $errors = $this->validator->validate($etudiant);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('etablissement_student_new');
        }

        $this->entityManager->persist($etudiant);
        $this->entityManager->flush();
        $this->addFlash('success', 'Student created successfully.');
        return $this->redirectToRoute('etablissement_dashboard');
    }

    return $this->render('Backoffice/etablissement/student_form.html.twig', [
        'title' => 'Add New Student',
        'etudiant' => null,
        'action' => $this->generateUrl('etablissement_student_new'),
        'csrf_token' => $this->csrfTokenManager->getToken('student_create')->getValue(),
    ]);
}

    #[Route('/student/{id}/edit', name: 'etablissement_student_edit', methods: ['GET', 'POST'])]
    public function editStudent(int $id, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Etablissement) {
            throw $this->createAccessDeniedException('User not authenticated or not an Etablissement');
        }

        $etudiant = $this->etudiantRepository->find($id);
        if (!$etudiant || $etudiant->getEtablissement() !== $user) {
            throw $this->createNotFoundException('Student not found or not associated with your Etablissement');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $token = new CsrfToken('student_edit', $data['_token'] ?? '');
            if (!$this->csrfTokenManager->isTokenValid($token)) {
                $this->addFlash('error', 'Invalid CSRF token.');
                return $this->redirectToRoute('etablissement_student_edit', ['id' => $id]);
            }

            $etudiant->setNom($data['nom'] ?? $etudiant->getNom());
            $etudiant->setPrenom($data['prenom'] ?? $etudiant->getPrenom());
            $etudiant->setEmail($data['email'] ?? $etudiant->getEmail());
            try {
                $etudiant->setDateNaissance(new \DateTime($data['date_naissance'] ?? $etudiant->getDateNaissance()->format('Y-m-d')));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Invalid date of birth.');
                return $this->redirectToRoute('etablissement_student_edit', ['id' => $id]);
            }
            $etudiant->setNumeroEtudiant($data['numero_etudiant'] ?? $etudiant->getNumeroEtudiant());

            $errors = $this->validator->validate($etudiant);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('etablissement_student_edit', ['id' => $id]);
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Student updated successfully.');
            return $this->redirectToRoute('etablissement_dashboard');
        }

        return $this->render('Backoffice/etablissement/student_form.html.twig', [
            'title' => 'Edit Student',
            'etudiant' => $etudiant,
            'action' => $this->generateUrl('etablissement_student_edit', ['id' => $id]),
            'csrf_token' => $this->csrfTokenManager->getToken('student_edit')->getValue(),
        ]);
    }

    #[Route('/student/{id}/delete', name: 'etablissement_student_delete', methods: ['POST'])]
    public function deleteStudent(int $id, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Etablissement) {
            throw $this->createAccessDeniedException('User not authenticated or not an Etablissement');
        }

        $etudiant = $this->etudiantRepository->find($id);
        if (!$etudiant || $etudiant->getEtablissement() !== $user) {
            throw $this->createNotFoundException('Student not found or not associated with your Etablissement');
        }

        $token = new CsrfToken('student_delete', $request->request->get('_token') ?? '');
        if ($this->csrfTokenManager->isTokenValid($token)) {
            $this->entityManager->remove($etudiant);
            $this->entityManager->flush();
            $this->addFlash('success', 'Student deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('etablissement_dashboard');
    }
}