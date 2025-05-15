<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Universite;
use App\Entity\Etablissement;
use App\Form\RegistrationType;

use App\Form\UserType;
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
    
            if ($role === 'ROLE_UNIVERSITE') {
                $user = new Universite();
                $user->setNom($request->request->get('nom_universite'));
            } elseif ($role === 'ROLE_ETABLISSEMENT') {
                $user = new Etablissement();
                $user->setNom($request->request->get('nom_etablissement'));
                $user->setEtype($request->request->get('etype'));
                $user->setLocalisation($request->request->get('localisation'));
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
                
                // Link to university if provided
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
    
        // For GET request, show the form
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
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
