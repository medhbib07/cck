<?php

namespace App\Controller;

use App\Entity\Etablissement;
use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class ContactController extends AbstractController
{
    private $emailService;
    private $logger;

    public function __construct(EmailService $emailService, LoggerInterface $logger)
    {
        $this->emailService = $emailService;
        $this->logger = $logger;
    }

    #[Route('/contact/{id}', name: 'app_contact_etablissement')]
    public function contactEtablissement(Request $request, Etablissement $etablissement): Response
    {
        $errors = [];
        $form_data = []; // Always initialize form_data

        if ($request->isMethod('POST')) {
            // Validate CSRF token
            if (!$this->isCsrfTokenValid('contact_etablissement', $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                $this->logger->error('CSRF validation failed for contact_etablissement');
                return $this->render('etablissement.html.twig', [
                    'etablissement' => $etablissement,
                    'form_errors' => [],
                    'form_data' => $request->request->all()['contact_etablissement'] ?? []
                ]);
            }

            // Extract form data
            $form_data = $request->request->all()['contact_etablissement'] ?? [];

            // Simple validation
            if (empty($form_data['nom'])) {
                $errors[] = ['propertyPath' => '[nom]', 'message' => 'Le nom est requis.'];
            }
            if (empty($form_data['email']) || !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['propertyPath' => '[email]', 'message' => 'L\'email est requis et doit être valide.'];
            }
            if (empty($form_data['niveau_etude']) || !in_array($form_data['niveau_etude'], ['bac', 'licence', 'master', 'doctorat', 'autre'])) {
                $errors[] = ['propertyPath' => '[niveau_etude]', 'message' => 'Le niveau d\'étude est requis et doit être valide.'];
            }
            if (empty($form_data['message'])) {
                $errors[] = ['propertyPath' => '[message]', 'message' => 'Le message est requis.'];
            }
            if (!empty($form_data['telephone']) && !preg_match('/^\+?[0-9\s\-]{8,20}$/', $form_data['telephone'])) {
                $errors[] = ['propertyPath' => '[telephone]', 'message' => 'Le numéro de téléphone n\'est pas valide.'];
            }

            if (empty($errors)) {
                // Prepare email content
                $subject = "Nouveau message de {$form_data['nom']} via CCK";
                $messageContent = "
                    <h3>Informations de l'étudiant:</h3>
                    <p><strong>Nom:</strong> {$form_data['nom']}</p>
                    <p><strong>Email:</strong> {$form_data['email']}</p>
                    <p><strong>Téléphone:</strong> " . ($form_data['telephone'] ?? 'Non fourni') . "</p>
                    <p><strong>Niveau d'étude:</strong> {$form_data['niveau_etude']}</p>
                    
                    <h3>Message:</h3>
                    <p>" . nl2br(htmlspecialchars($form_data['message'])) . "</p>
                    
                    <h3>À propos de l'établissement:</h3>
                    <p><strong>Établissement:</strong> {$etablissement->getNom()}</p>
                    <p><strong>Université:</strong> " . ($etablissement->getGroupe() ? $etablissement->getGroupe()->getNom() : 'Non affilié') . "</p>
                ";

                // Send email using EmailService
                $recipientEmail = $etablissement->getEmail() ?? 'contact@cck.tn';
                $recipientName = $etablissement->getNom();
                $sent = $this->emailService->send(
                    $recipientEmail,
                    $recipientName,
                    $subject,
                    $messageContent,
                    strip_tags($messageContent)
                );

                if ($sent) {
                    $this->addFlash('success', 'Votre message a été envoyé avec succès à l\'établissement.');
                    return $this->redirectToRoute('app_view_universities');
                } else {
                    $this->addFlash('error', 'Une erreur s\'est produite lors de l\'envoi du message.');
                    $this->logger->error('Email sending failed for etablissement: ' . $etablissement->getId());
                }
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error['message']);
                }
            }
        }

        $this->logger->info('Rendering etablissement.html.twig', [
            'etablissement_id' => $etablissement->getId(),
            'form_data_keys' => array_keys($form_data)
        ]);

        return $this->render('user/contact.html.twig', [
            'etablissement' => $etablissement,
            'form_errors' => $errors,
            'form_data' => $form_data
        ]);
    }
}