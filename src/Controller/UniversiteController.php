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

             if ($request->query->get('export') === 'csv') {
        $filename = 'etablissements_' . date('Ymd_His') . '.csv';

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $handle = fopen('php://output', 'w+');

        // Add UTF-8 BOM to fix Excel encoding issues
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV header
        fputcsv($handle, ['Name', 'Type', 'Location', 'Website', 'Email']);

        // CSV content
        foreach ($establishments as $etablissement) {
            fputcsv($handle, [
                $etablissement->getNom(),
                $etablissement->getEtype(),
                $etablissement->getVille(),
                $etablissement->getAdresse(),
                $etablissement->getCodePostal(),
                $etablissement->getSiteweb() ?? 'N/A',
                $etablissement->getEmail()
            ]);
        }

        fclose($handle);
        $response->send();
        exit; // stop further rendering
    }
    
       // Check if export requested
        if ($request->query->get('export') === 'pdf') {
            // Configure Dompdf
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $dompdf = new Dompdf($options);

            // Render HTML from Twig
            $html = $this->renderView('Backoffice/universite/pdf_export.html.twig', [
                'universite' => $universite,
                'establishments' => $establishments,
                'date' => new \DateTime(),
            ]);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Stream PDF to browser
            return new Response($dompdf->stream('etablissements.pdf', [
                'Attachment' => true, // set to false to show in browser
            ]));
        }
    
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
           
            

            // Map the 'etype' string to the corresponding constant
                $etypeMap = [
                    'Publique' => Etablissement::ETYPE_PUBLIC,
                    'Privé' => Etablissement::ETYPE_PRIVATE,
                ];
                $etype = $request->request->get('etype');
                if (isset($etypeMap[$etype])) {
                    $etablissement->setEtype($etypeMap[$etype]);
                } else {
                    $errors[] = "Invalid establishment type.";
                }
    
            // Email validation
            if (!filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }
            $submittedToken = $request->request->get('_token');

                if (!$this->isCsrfTokenValid('establishment', $submittedToken)) {
                    $errors[] = 'Invalid CSRF token.';
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
    
        // Fetch establishments again after potential error
        $establishments = $em->getRepository(Etablissement::class)
            ->findBy(['groupe' => $universite]);
            
        $stats = $em->getRepository(Etablissement::class)
    ->getEstablishmentStats($universite);
        return $this->render('Backoffice/universite/dashboard.html.twig', [
            'establishments' => $establishments,
            'errors' => $errors,
            'form_data' => $request->request->all(),
            'universite' => $universite,
            'stats' => $stats,
            'searchForm' => $searchForm->createView()
        ]);
    }
#[Route('/{id}/update', name: 'universite_update', methods: ['POST'])]
    public function update(
        Request $request,
        int $id,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
            ): Response {
        // Get the current user (university)
                $errors = [];

        $universite = $this->getUser();
        if (!$universite) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        // Find the establishment
        $etablissement = $em->getRepository(Etablissement::class)->find($id);
        if (!$etablissement) {
            throw $this->createNotFoundException('Établissement non trouvé.');
        }

        // Verify the establishment belongs to the current university
        if ($etablissement->getGroupe() !== $universite) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres établissements.');
        }

        // Verify CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('establishment', $submittedToken)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('universite_index');
        }

        // Update fields from form
        $etablissement->setNom($request->request->get('nom'));
        $etablissement->setEtype($request->request->get('etype'));
        $etablissement->setAdresse($request->request->get('adresse'));
        $etablissement->setVille($request->request->get('ville') ?? null);
        $etablissement->setCodePostal($request->request->get('code_postal') ?? null);
        $etablissement->setLatitude((float) $request->request->get('latitude'));
        $etablissement->setLongitude((float) $request->request->get('longitude'));
        $etablissement->setCapacite((int) $request->request->get('capacite'));
        $etablissement->setDescription($request->request->get('description') ?? null);
        $etablissement->setSiteweb($request->request->get('siteweb') ?? null);
        $etablissement->setEmail($request->request->get('email'));

        // Handle date creation
        $dateCreation = $request->request->get('date_creation');
        if ($dateCreation) {
            try {
                $etablissement->setDateCreation(new \DateTime($dateCreation));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Date de création invalide.');
                return $this->redirectToRoute('universite_index');
            }
        } else {
            $this->addFlash('error', 'La date de création est requise.');
            return $this->redirectToRoute('universite_index');
        }

        // Handle password (only update if provided)
        $password = $request->request->get('password');
        if ($password) {
            try {
                $hashedPassword = $passwordHasher->hashPassword($etablissement, $password);
                $etablissement->setPassword($hashedPassword);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour du mot de passe.');
                return $this->redirectToRoute('universite_index');
            }
        }

        // Handle logo file upload
        if ($request->files->has('logo')) {
            $logoFile = $request->files->get('logo');
            if ($logoFile) {
                try {
                    $newFilename = uniqid() . '.' . $logoFile->guessExtension();
                    $logoFile->move(
                        $this->getParameter('logos_directory'),
                        $newFilename
                    );
                    // Remove old logo if it exists
                    if ($etablissement->getLogo()) {
                        $oldFilePath = $this->getParameter('logos_directory') . '/' . $etablissement->getLogo();
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                    $etablissement->setLogo($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement du logo.');
                    return $this->redirectToRoute('universite_index');
                }
            }
        }

        
            // Re-render the dashboard with errors
            $establishments = $em->getRepository(Etablissement::class)->findBy(['groupe' => $universite]);
            $stats = $em->getRepository(Etablissement::class)->getEstablishmentStats($universite);
            $em->flush();
            $this->addFlash('success', 'Établissement mis à jour avec succès.');

            return $this->render('Backoffice/universite/dashboard.html.twig', [
                'establishments' => $establishments,
                'errors' => $errors,
                'form_data' => $request->request->all(),
                'universite' => $universite,
                'stats' => $stats,
                'searchForm' => $this->createFormBuilder()
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
                    ->getForm()->createView()
            ]);
        

        return $this->redirectToRoute('universite_index');
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



    #[Route('/universite/updateProfile', name: 'universite_update_profile', methods: ['GET', 'POST'])]
   public function updateProfile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $universite = $this->getUser();
        if (!$universite instanceof Universite) {
            throw $this->createAccessDeniedException('User is not a Universite.');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            // Manual validation
            $errors = [];

            // Validate nom
            if (empty($data['nom'])) {
                $errors[] = 'University name cannot be empty.';
            } elseif (strlen($data['nom']) > 255) {
                $errors[] = 'University name cannot exceed 255 characters.';
            }

            // Validate email
            if (empty($data['email'])) {
                $errors[] = 'Email cannot be empty.';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'The email address is not valid.';
            }

            

            // Validate password (optional, only if provided)
            if (!empty($data['password']) && strlen($data['password']) < 6) {
                $errors[] = 'Password must be at least 6 characters long.';
            }

            // Check email uniqueness
            $existingUser = $entityManager->getRepository(Universite::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser && $existingUser->getId() !== $universite->getId()) {
                $errors[] = 'This email is already in use.';
            }

            // If there are errors, display them
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('Backoffice/universite/dashboard.html.twig', [
                    'universite' => $universite,
                ]);
            }


            // Update fields
            $universite->setNom($data['nom']);
            $universite->setEmail($data['email']);

            // Handle password update
            if (!empty($data['password'])) {
                $hashedPassword = $passwordHasher->hashPassword($universite, $data['password']);
                $universite->setPassword($hashedPassword);
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
    
        // Fetch establishments again after potential error
        $establishments = $entityManager->getRepository(Etablissement::class)
            ->findBy(['groupe' => $universite]);

        $stats = $entityManager->getRepository(Etablissement::class)
            ->getEstablishmentStats($universite);

            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully.');
            return $this->redirectToRoute('universite_index'); // Adjust to your dashboard route
        }

        return $this->render('Backoffice/universite/dashboard.html.twig', [
            'universite' => $universite,
            'searchForm' => $searchForm->createView(),
            'establishments' => $establishments,
            'stats' => $stats,
        ]);

    }
}