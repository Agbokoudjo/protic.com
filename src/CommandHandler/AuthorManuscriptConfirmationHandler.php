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
use App\Service\Mailing\SystemMailer;

final class AuthorManuscriptConfirmationHandler
{
    public function __construct(
        private readonly SystemMailer $systemMailer,
        private ManuscriptSubmissionRepository $submissionRequestRepo,
    ) {}

    public function handle(string|int $id): void
    {
        /**
         * @var ManuscriptSubmission|null 
         */
        $submission = $this->submissionRequestRepo->find($id);

        if (!($submission instanceof ManuscriptSubmission)) {
            return;
        }

        $this->systemMailer->send(
            recipientAddress: $submission->getEmail(),
            subject: "ProTIC — Votre demande '{$submission->getSubject()}' a bien été reçue",
            templatePath: 'email/confirmation_author.html.twig',
            context: [
                'submission' => $submission
            ]
        );
    }
}