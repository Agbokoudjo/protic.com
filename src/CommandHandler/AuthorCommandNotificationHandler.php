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

use App\Entity\Book;
use App\Entity\ContactRequest;
use App\Repository\ContactRequestRepository;
use App\Service\GlobalSettingProvider;
use App\Service\Mailing\SupportMailer;

final class AuthorCommandNotificationHandler{

    public function __construct(
        private readonly ContactRequestRepository $contactRequestModel,
        private readonly SupportMailer $supportMailer,
        private readonly GlobalSettingProvider $globalSettingProvider
    ) {}

    public function handle(string|int $id):void{

        $contactRequest=$this->contactRequestModel->find($id);

        if(!($contactRequest instanceof ContactRequest)){ return ;}

        try {
            $book=$contactRequest->getBook();
            
            if (!($book instanceof Book)) {
                return;
            }

            $author = $book?->getAuthor();

            $this->supportMailer->sendManager(
                recipientEmail: $author->getEmail() ?? $this->globalSettingProvider->getSettings()->getEmailContact(),
                subject: '[ProTIC] Nouvelle demande — ' . $contactRequest->getSubject(),
                htmlTemplate: 'email/contact_author_notification.html.twig',
                context: [
                    'contact' => $contactRequest,
                    'book'    => $book,
                    'authorFullName'  => $author->getFullName()
                ],
                senderEmail: null,            // utilise la config 'support'
                replyToEmail: $contactRequest->getFullName() . ' <' . $contactRequest->getEmail() . '>',
            );
        } catch (\Throwable) {
            /* L'email échoue silencieusement — la demande est déjà sauvegardée */
        }

    }     
}