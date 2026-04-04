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

namespace App\Service\Mailing;

/**
 * Interface pour l'envoi de notifications par email.
 * 
 * Permet d'envoyer par exemple les données soumises via un formulaire de contact
 * aux administrateurs du système.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Application\Service\Mailer
 */
interface SupportMailerInterface
{
    /**
     * Envoie un email de notification aux administrateurs.
     *
     * Utilise un template Twig pour générer le contenu HTML de l'email.
     * L'adresse 'From' doit correspondre à celle configurée dans MAILER_DSN.
     *
     * @param string $senderEmail L'adresse email utilisée dans le champ 'From' (doit correspondre à l'authentification SMTP)
     * @param  string|array $recipientEmail L'adresse email du destinataire (administrateur)
     * @param string $subject L'objet de l'email
     * @param string $htmlTemplate Le chemin vers le template Twig (ex: 'contact/contact_notification.html.twig')
     * @param array<string, mixed>|null $context Variables à passer au template Twig
     * @param string|null $replyToEmail Adresse email de réponse (généralement celle du client)
     * @param array $attachments = [] // Nouveau : ['path/to/file' => 'alias_ou_nom'] 
     * @return void
     */
    public function sendManager(
        string|array $recipientEmail,
        string $subject,
        string $htmlTemplate,
        ?array $context,
        ? string $senderEmail,
        ?string $replyToEmail,
        ?array $attachments = [] // Nouveau : ['path/to/file' => 'alias_ou_nom']
    ): void;
}
