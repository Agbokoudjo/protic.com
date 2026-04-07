<?php

declare(strict_types=1);

namespace App\Controller;

use App\CommandHandler\AdminManuscriptNotificationHandler;
use App\CommandHandler\AuthorManuscriptConfirmationHandler;
use App\Entity\ManuscriptSubmission;
use App\Form\ManuscriptSubmissionType;
use App\QueueHandler\AsyncMethodDispatcher;
use App\Repository\ManuscriptSubmissionRepository;
use App\Service\ProcessingErrorFormHandle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
final class ContactController extends AbstractController
{
    public function __construct(
        private readonly ManuscriptSubmissionRepository $submissionRepo,
        private readonly AsyncMethodDispatcher $asyncDispatcher
    ) {}

    public function __invoke(
        Request $request,
        ProcessingErrorFormHandle $formErrorHandle): Response
    {
        $submission = new ManuscriptSubmission();
        $form  = $this->createForm(ManuscriptSubmissionType::class, $submission);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            if (!$form->isValid()) {
                return $this->json([
                    'title' => 'Erreur de validation',
                    'details' => 'Certaines informations sont invalides ou manquantes. Veuillez corriger les champs concernés et réessayer.',
                    'violations' => $formErrorHandle->handle(
                        $form,
                        'validators',
                        $request->getLocale()
                    ),
                    'formName' => $form->getName()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $submission->setStatus('pending');
            $submission->setSubmittedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));

            $this->submissionRepo->add($submission,true);

            /* Email de notification à ProTIC */
            $this->asyncDispatcher->dispatch(
                AdminManuscriptNotificationHandler::class,
                    'handle',
                    [$submission->getId()]
            );

            /* Email de confirmation à l'auteur */
            $this->asyncDispatcher->dispatch(
                AuthorManuscriptConfirmationHandler::class,
                'handle',
                [$submission->getId()]
            );

            /* ── 6. Flash + redirect (PRG pattern) ── */
            $this->addFlash(
                'contact_success',
                sprintf(
                    'Merci %s ! Votre demande a bien été reçue. Nous vous répondrons sous 48h à l\'adresse %s.',
                    htmlspecialchars($submission->getFullName()),
                    htmlspecialchars($submission->getEmail())
                )
            );

            return $this->redirectToRoute('app_contact',['_fragment' => 'container-succcess']);
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
