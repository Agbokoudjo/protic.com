<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * LinkedIn: https://www.linkedin.com/in/internationales-web-apps-services-120520193/
 * Github: https://github.com/Agbokoudjo/
 * Company: INTERNATIONALES WEB APPS & SERVICES
 *
 * For more information, please feel free to contact the author.
 */

namespace App\CommandHandler;

use App\Entity\ManuscriptSubmission;
use App\Repository\ManuscriptSubmissionRepository;
use App\Service\GlobalSettingProvider;
use App\Service\Mailing\SupportMailer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class AdminManuscriptNotificationHandler
{
    public function __construct(
        private ManuscriptSubmissionRepository $submissionRequestRepo,
        private SupportMailer $supportMailer,
        private readonly GlobalSettingProvider $settingProvider,
        private readonly ParameterBagInterface $params,
    ) {}

    public function handle(string|int $id): void
    {
        /**
         * @var ManuscriptSubmission|null 
         */
        $submissionRequest = $this->submissionRequestRepo->find($id);

        if (!($submissionRequest instanceof ManuscriptSubmission) ) {
            return;
        }

        $emailContact = $this->settingProvider->getSettings()->getEmailContact();

        // Construction du chemin
        $uploadDir = sprintf(
            '%s/var/uploads/manuscripts/%s',
            $this->params->get('kernel.project_dir'),
            $submissionRequest->getManuscriptfilename()
        );

        // Nettoyage du nom pour la pièce jointe
        $fullName = trim(preg_replace('/\s+/', '_', $submissionRequest->getFullName()));
        $fileName = sprintf('Manuscript_%s.pdf', $fullName);

        // Préparation des pièces jointes (seulement si le fichier existe)
        $attachments = [];
        if (file_exists($uploadDir)) {
            $attachments[$uploadDir] = $fileName;
        }

        $this->supportMailer->sendManager(
            recipientEmail: $emailContact,
            subject: $submissionRequest->getSubject() ?? 'Nouvelle soumission de manuscrit',
            htmlTemplate: 'email/notification_submission.html.twig',
            context: ['submission' => $submissionRequest],
            senderEmail: null,
            replyToEmail: $submissionRequest->getEmail(),
            attachments: $attachments
        );
    }
}
