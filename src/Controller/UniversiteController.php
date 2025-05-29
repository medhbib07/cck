<?php

namespace App\Controller;

use App\Entity\Etablissement;
use App\Entity\Universite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Dompdf\Dompdf;
use Dompdf\Options;
#[Route('/universite')]
class UniversiteController extends AbstractController
{
    #[Route('/', name: 'universite_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        // Get the logged-in university user
        $universite = $this->getUser();
        
        if (!$universite instanceof Universite) {
            throw new AccessDeniedException('Only university users can access this page');
        }
    
        // Create search form
        $searchForm = $this->createFormBuilder()
            ->setMethod('GET')
            ->add('search', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Search by name or location']
            ])
            ->add('type', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'All Types' => null,
                    'Public' => Etablissement::ETYPE_PUBLIC,
                    'Private' => Etablissement::ETYPE_PRIVATE
                ]
            ])
            ->add('filter', SubmitType::class, [
                'label' => 'Filter',
                'attr' => ['class' => 'bg-blue-600 text-white px-4 py-2 rounded']
            ])
            ->getForm();
    
        $searchForm->handleRequest($request);
    
        // Get filtered establishments
        $establishments = $em->getRepository(Etablissement::class)
            ->createQueryBuilder('e')
            ->where('e.groupe = :universite')
            ->setParameter('universite', $universite);
    
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $data = $searchForm->getData();
            
            if (!empty($data['search'])) {
                $establishments->andWhere('e.nom LIKE :search OR e.adresse LIKE :search')
                    ->setParameter('search', '%'.$data['search'].'%');
            }
            
            if (!empty($data['type'])) {
                $establishments->andWhere('e.etype = :type')
                    ->setParameter('type', $data['type']);
            }
        }
    
        $establishments = $establishments->getQuery()->getResult();
    
        // Get statistics
        $stats = $em->getRepository(Etablissement::class)
            ->getEstablishmentStats($universite);
    
        // // Handle PDF export
        // if ($request->query->get('export') === 'pdf') {
        //     return $this->exportToPdf($establishments, $universite);
        // }
    
        return $this->render('Backoffice/universite/dashboard.html.twig', [
            'establishments' => $establishments,
            'universite' => $universite,
            'stats' => $stats,
            'searchForm' => $searchForm->createView(),
            'errors' => []
        ]);
    }
    
    // private function exportToPdf(array $establishments, Universite $universite): Response
    // {
    //     $html = $this->renderView('Backoffice/universite/pdf_export.html.twig', [
    //         'establishments' => $establishments,
    //         'universite' => $universite,
    //         'date' => new \DateTime()
    //     ]);
    
    //     $dompdf = new Dompdf();
    //     $dompdf->loadHtml($html);
    //     $dompdf->setPaper('A4', 'landscape');
    //     $dompdf->render();
    
    //     return new Response(
    //         $dompdf->output(),
    //         Response::HTTP_OK,
    //         [
    //             'Content-Type' => 'application/pdf',
    //             'Content-Disposition' => 'attachment; filename="establishments_export_'.date('Y-m-d').'.pdf"'
    //         ]
    //     );
    // }

    #[Route('/create', name: 'universite_create', methods: ['POST'])]
    public function create(
        Request $request, 
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $universite = $this->getUser();
        $errors = [];
        $etablissement = new Etablissement();
    
        try {
            // Required fields validation
            $requiredFields = ['nom', 'etype', 'localisation', 'email', 'password'];
            foreach ($requiredFields as $field) {
                if (!$request->request->get($field)) {
                    $errors[] = "Field $field is required";
                }
            }
    
            // Email validation
            if (!filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }
    
            if (empty($errors)) {
                // Set user properties
                $etablissement
                    ->setEmail($request->request->get('email'))
                    ->setPassword($passwordHasher->hashPassword(
                        $etablissement,
                        $request->request->get('password')
                    ))
                    ->setRoles(['ROLE_ETABLISSEMENT']);
    
                // Set establishment properties
                $etablissement
                    ->setNom($request->request->get('nom'))
                    ->setEtype($request->request->get('etype'))
                    ->setSiteweb($request->request->get('siteweb'))
                    ->setGroupe($universite)
                    ->setAdresse($request->request->get('adresse'))
                    ->setVille($request->request->get('ville'))
                    ->setCodePostal($request->request->get('code_postal'))
                    ->setLatitude($request->request->get('latitude'))
                    ->setLongitude($request->request->get('longitude'))
                    ->setCapacite((int) $request->request->get('capacite'))
                    ->setDescription($request->request->get('description'))
                    ->setDateCreation(new \DateTime($request->request->get('date_creation')));

    
                // Handle file upload
                $logoFile = $request->files->get('logo');
                if ($logoFile) {
                    $newFilename = uniqid().'.'.$logoFile->guessExtension();
                    $logoFile->move(
                        $this->getParameter('logos_directory'),
                        $newFilename
                    );
                    $etablissement->setLogo($newFilename);
                }
    
                $em->persist($etablissement);
                $em->flush();
    
                $this->addFlash('success', 'Establishment created successfully');
                return $this->redirectToRoute('universite_index');
            }
        } catch (\Exception $e) {
            $errors[] = 'Error creating establishment: '.$e->getMessage();
        }
    
        // Fetch establishments again after potential error
        $establishments = $em->getRepository(Etablissement::class)
            ->findBy(['groupe' => $universite]);
            
    
        return $this->render('Backoffice/universite/dashboard.html.twig', [
            'establishments' => $establishments,
            'errors' => $errors,
            'form_data' => $request->request->all(),
            'universite' => $universite
        ]);
    }

    #[Route('/{id}/update', name: 'universite_update', methods: ['POST'])]
    public function update(Request $request, Etablissement $etablissement, EntityManagerInterface $em): Response
    {
        $universite = $this->getUser();
        $errors = [];

        // Verify the establishment belongs to the current university
        if ($etablissement->getGroupe() !== $universite) {
            throw new AccessDeniedException('You can only edit your own establishments');
        }

        try {
            $etablissement->setNom($request->request->get('nom'))
                ->setEtype($request->request->get('etype'))
                ->setLocalisation($request->request->get('localisation'))
                ->setSiteweb($request->request->get('siteweb'));

            $em->flush();
            $this->addFlash('success', 'Establishment updated successfully');
            return $this->redirectToRoute('universite_index');

        } catch (\Exception $e) {
            $errors[] = 'Error updating establishment: '.$e->getMessage();
        }

        // Fetch establishments again after potential error
        $establishments = $em->getRepository(Etablissement::class)
            ->findBy(['groupe' => $universite]);

        return $this->render('Backoffice/universite/dashboard.html.twig', [
            'establishments' => $establishments,
            'errors' => $errors,
            'form_data' => $request->request->all(),
            'universite' => $universite
        ]);
    }

    #[Route('/{id}/delete', name: 'universite_delete', methods: ['POST'])]
    public function delete(Etablissement $etablissement, EntityManagerInterface $em): Response
    {
        $universite = $this->getUser();

        // Verify the establishment belongs to the current university
        if ($etablissement->getGroupe() !== $universite) {
            throw new AccessDeniedException('You can only delete your own establishments');
        }

        try {
            $em->remove($etablissement);
            $em->flush();
            $this->addFlash('success', 'Establishment deleted successfully');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting establishment');
        }
        
        return $this->redirectToRoute('universite_index');
    }
}