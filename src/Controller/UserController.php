<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Universite;
use App\Entity\Etablissement;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $role = $request->request->get('role');
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            $existingUniversite = $entityManager->getRepository(Universite::class)->findOneBy(['email' => $email]);
            $existingEtablissement = $entityManager->getRepository(Etablissement::class)->findOneBy(['email' => $email]);
            
            $errors = [];
                if ($existingUser) {
                    $errors[] = 'User email already exists';
                }
                if ($existingUniversite) {
                    $errors[] = 'Universite email already exists';
                }
                if ($existingEtablissement) {
                    $errors[] = 'Etablissement email already exists';
                }

                if (!empty($errors)) {
                    $this->addFlash('error', 'Cet email est déjà utilisé.');
                    return $this->redirectToRoute('app_register');
                }

            if ($role === 'ROLE_UNIVERSITE') {
                $user = new Universite();
                $user->setNom($request->request->get('nom_universite'));
            } elseif ($role === 'ROLE_ETABLISSEMENT') {
                $user = new Etablissement();
                $user->setNom($request->request->get('nom_etablissement'));
                $user->setEtype($request->request->get('etype'));
                $user->setAdresse($request->request->get('adresse'));
                $user->setCodePostal($request->request->get('code_postal'));
                $user->setVille($request->request->get('ville'));
                $user->setLatitude($request->request->get('latitude') ? (float)$request->request->get('latitude') : null);
                $user->setLongitude($request->request->get('longitude') ? (float)$request->request->get('longitude') : null);
                $user->setTelephone($request->request->get('telephone'));
                $user->setDescription($request->request->get('description'));
                try {
                    $dateCreation = $request->request->get('date_creation') ? new \DateTime($request->request->get('date_creation')) : null;
                    $user->setDateCreation($dateCreation);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Date de création invalide.');
                    return $this->redirectToRoute('app_register');
                }
                $user->setCapacite($request->request->get('capacite') ? (int)$request->request->get('capacite') : null);
                $user->setSiteweb($request->request->get('siteweb'));

                $logoFile = $request->files->get('logo');
                if ($logoFile) {
                    $newFilename = uniqid().'.'.$logoFile->guessExtension();
                    $logoFile->move(
                        $this->getParameter('logos_directory'),
                        $newFilename
                    );
                    $user->setLogo($newFilename);
                }

                if ($universiteId = $request->request->get('universite_id')) {
                    $universite = $entityManager->getRepository(Universite::class)->find($universiteId);
                    if ($universite) {
                        $user->setGroupe($universite);
                    }
                }
            } else {
                $user = new User();
            }

            $user->setEmail($email);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setRoles([$role]);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_login');
        }

        $universites = $entityManager->getRepository(Universite::class)->findAll();

        return $this->render('user/register.html.twig', [
            'universites' => $universites,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
