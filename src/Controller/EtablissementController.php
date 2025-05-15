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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Knp\Snappy\Pdf;
use League\Csv\Writer;

#[Route('/etablissement')]
class EtablissementController extends AbstractController
{
    private $entityManager;
    private $etudiantRepository;
    private $etablissementRepository;
    private $validator;
    private $csrfTokenManager;
    private $pdfGenerator;

    public function __construct(
        EntityManagerInterface $entityManager,
        EtudiantRepository $etudiantRepository,
        EtablissementRepository $etablissementRepository,
        ValidatorInterface $validator,
        CsrfTokenManagerInterface $csrfTokenManager,
        Pdf $pdfGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->etudiantRepository = $etudiantRepository;
        $this->etablissementRepository = $etablissementRepository;
        $this->validator = $validator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->pdfGenerator = $pdfGenerator;
        
    }

#[Route('/dashboard', name: 'etablissement_dashboard', methods: ['GET', 'POST'])]
public function dashboard(Request $request): Response
{
    $user = $this->getUser();
    if (!$user instanceof Etablissement) {
        throw $this->createAccessDeniedException('User not authenticated or not an Etablissement');
    }

    $search = $request->query->get('search', '');
    $page = $request->query->getInt('page', 1);
    $limit = 5;

    // Handle export requests
    if ($request->isMethod('POST')) {
        $exportType = $request->request->get('export_type');
        $tokenId = $exportType === 'pdf' ? 'export_pdf' : 'export_csv';
        $token = new CsrfToken($tokenId, $request->request->get('_token'));

        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('etablissement_dashboard');
        }

        $students = $this->etudiantRepository->findByEtablissementWithSearch($user, $search);

        if ($exportType === 'pdf') {
            $html = $this->renderView('Backoffice/etablissement/pdf_export.html.twig', [
                'students' => $students,
                'etablissement' => $user,
            ]);

            return new StreamedResponse(function() use ($html) {
                echo $this->pdfGenerator->getOutputFromHtml($html);
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="students_export.pdf"',
            ]);
        }

        if ($exportType === 'csv') {
            $csv = Writer::createFromString();
            $csv->insertOne(['Name', 'Email', 'DOB', 'Student #', 'CIN', 'Section', 'Score', 'Niveau']);
            foreach ($students as $student) {
                $csv->insertOne([
                    $student->getNom() . ' ' . $student->getPrenom(),
                    $student->getEmail(),
                    $student->getDateNaissance()->format('Y-m-d'),
                    $student->getNumeroEtudiant(),
                    $student->getNumCin(),
                    $student->getSection(),
                    $student->getScore() ?? 'N/A',
                    $student->getNiveau(),
                ]);
            }

            return new StreamedResponse(function() use ($csv) {
                echo $csv->toString();
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="students_export.csv"',
            ]);
        }
    }

    // Fetch students for display
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
        'uniqueEtablissements' => 1,
        'averageScore' => $this->etudiantRepository->createQueryBuilder('e')
            ->select('AVG(e.score)')
            ->where('e.etablissement = :etablissement')
            ->setParameter('etablissement', $user)
            ->getQuery()
            ->getSingleScalarResult(),
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
                    WHEN (DATE_DIFF(CURRENT_DATE(), u.dateNaissance) / 365.25) < 18 THEN '<18'
                    WHEN (DATE_DIFF(CURRENT_DATE(), u.dateNaissance) / 365.25) >= 18 AND (DATE_DIFF(CURRENT_DATE(), u.dateNaissance) / 365.25) <= 25 THEN '18-25'
                    WHEN (DATE_DIFF(CURRENT_DATE(), u.dateNaissance) / 365.25) > 25 AND (DATE_DIFF(CURRENT_DATE(), u.dateNaissance) / 365.25) <= 35 THEN '26-35'
                    ELSE '>35'
                END as range,
                COUNT(u.id) as count
            ")
            ->where('u.etablissement = :etablissement')
            ->setParameter('etablissement', $user)
            ->groupBy('range')
            ->getQuery()
            ->getResult(),
        'score_data' => $this->etudiantRepository->createQueryBuilder('u')
            ->select("
                CASE
                    WHEN u.score < 5 THEN '0-5'
                    WHEN u.score >= 5 AND u.score < 10 THEN '5-10'
                    WHEN u.score >= 10 AND u.score < 15 THEN '10-15'
                    WHEN u.score >= 15 AND u.score <= 20 THEN '15-20'
                    ELSE 'Unknown'
                END as range,
                COUNT(u.id) as count
            ")
            ->where('u.etablissement = :etablissement')
            ->andWhere('u.score IS NOT NULL')
            ->setParameter('etablissement', $user)
            ->groupBy('range')
            ->getQuery()
            ->getResult(),
        'section_data' => $this->etudiantRepository->createQueryBuilder('u')
            ->select('u.section, COUNT(u.id) as count')
            ->where('u.etablissement = :etablissement')
            ->setParameter('etablissement', $user)
            ->groupBy('u.section')
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
            $etudiant->setNumCin($data['num_cin'] ?? $etudiant->getNumCin());
            $etudiant->setSection($data['section'] ?? $etudiant->getSection());
            $etudiant->setScore(isset($data['score']) ? (float)$data['score'] : $etudiant->getScore());
            $etudiant->setNiveau($data['niveau'] ?? $etudiant->getNiveau());
            $etudiant->setLocalisation($data['localisation'] ?? $etudiant->getLocalisation());

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

#[Route('/import-students', name: 'etablissement_import_students', methods: ['POST'])]
public function importStudents(Request $request): Response
{
    $user = $this->getUser();
    if (!$user instanceof Etablissement) {
        throw $this->createAccessDeniedException('User not authenticated or not an Etablissement');
    }

    $token = new CsrfToken('import_students', $request->request->get('_token'));
    if (!$this->csrfTokenManager->isTokenValid($token)) {
        $this->addFlash('error', 'Invalid CSRF token.');
        return $this->redirectToRoute('etablissement_dashboard');
    }

    $file = $request->files->get('xml_file');
    if (!$file || $file->getClientOriginalExtension() !== 'xml') {
        $this->addFlash('error', 'Please upload a valid XML file.');
        return $this->redirectToRoute('etablissement_dashboard');
    }

    try {
        $xmlContent = file_get_contents($file->getPathname());
        $xml = new \SimpleXMLElement($xmlContent);

        $currentYear = (new \DateTime())->format('Y');

        // Get the max numeroEtudiant for current year, e.g. ETU2025xxx
        $maxNumero = $this->entityManager->getRepository(Etudiant::class)
            ->createQueryBuilder('e')
            ->select('MAX(e.numeroEtudiant)')
            ->where('e.numeroEtudiant LIKE :prefix')
            ->setParameter('prefix', 'ETU' . $currentYear . '%')
            ->getQuery()
            ->getSingleScalarResult();

        $lastSeq = 0;
        if ($maxNumero) {
            // Extract last 3 digits or more from maxNumeroEtudiant string
            // 'ETU' + 4 chars year = 7 chars total, rest is sequence
            $lastSeq = (int)substr($maxNumero, 7);
        }

        $successCount = 0;
        $errorMessages = [];

        foreach ($xml->student as $studentXml) {
            $etudiant = new Etudiant();
            $etudiant->setNom((string)($studentXml->nom ?? ''));
            $etudiant->setPrenom((string)($studentXml->prenom ?? ''));
            $etudiant->setEmail((string)($studentXml->email ?? ''));

            try {
                $dateNaissance = new \DateTime((string)($studentXml->dateNaissance ?? 'now'));
                $etudiant->setDateNaissance($dateNaissance);
            } catch (\Exception $e) {
                $errorMessages[] = "Invalid date of birth for student {$etudiant->getNom()} {$etudiant->getPrenom()}.";
                continue;
            }

            $etudiant->setNumCin((string)($studentXml->numCin ?? ''));
            $etudiant->setSection((string)($studentXml->section ?? ''));
            $score = (string)($studentXml->score ?? '');
            $etudiant->setScore($score !== '' ? (float)$score : null);
            $etudiant->setNiveau((string)($studentXml->niveau ?? ''));
            $etudiant->setLocalisation((string)($studentXml->localisation ?? ''));
            $etudiant->setEtablissement($user);

            // Generate unique numeroEtudiant
            $lastSeq++;
            $numeroEtudiant = sprintf('ETU%s%03d', $currentYear, $lastSeq);
            $etudiant->setNumeroEtudiant($numeroEtudiant);

            // Validate entity
            $errors = $this->validator->validate($etudiant);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $errorMessages[] = "Error for student {$etudiant->getNom()} {$etudiant->getPrenom()}: {$error->getMessage()}";
                }
                continue;
            }

            $this->entityManager->persist($etudiant);
            $successCount++;
        }

        $this->entityManager->flush();

        if ($successCount > 0) {
            $this->addFlash('success', "$successCount student(s) imported successfully.");
        }
        foreach ($errorMessages as $message) {
            $this->addFlash('error', $message);
        }
        if ($successCount === 0 && !empty($errorMessages)) {
            $this->addFlash('error', 'No students were imported due to errors.');
        }
    } catch (\Exception $e) {
        $this->addFlash('error', 'Failed to process XML file: ' . $e->getMessage());
    }

    return $this->redirectToRoute('etablissement_dashboard');
}



}