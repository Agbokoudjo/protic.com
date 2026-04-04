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

use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Interface pour la création et l'envoi d'emails.
 * 
 * Fournit des méthodes pour créer des emails à partir de templates Twig
 * et les envoyer de manière synchrone ou asynchrone via Symfony Mailer.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Infrastructure\Service\Mailing
 */
interface MailerFactoryInterface
{
    /**
     * Envoie un email de manière asynchrone (via Messenger si configuré).
     *
     * @param Email $email L'objet email à envoyer
     * 
     * @return void
     * 
     * @throws TransportExceptionInterface Si l'envoi échoue
     */
    public function sendAsync(Email $email): void;

    /**
     * Envoie un email de manière synchrone (immédiate).
     * 
     * Utilise cette méthode pour les emails critiques qui doivent
     * être envoyés immédiatement sans passer par une file d'attente.
     *
     * @param Email $email L'objet email à envoyer
     * 
     * @return void
     * 
     * @throws TransportExceptionInterface Si l'envoi échoue
     */
    public function sendNow(Email $email): void;

    /**
     * Crée un email basé sur un template Twig.
     *
     * @param string $senderAddress L'adresse email de l'expéditeur (doit correspondre à MAILER_DSN)
     * @param string $senderName Le nom affiché de l'expéditeur (ex: "MonApp System")
     * @param string|array $recipientAddress L'adresse email du destinataire
     * @param string $subject L'objet de l'email
     * @param string $templatePath Le chemin vers le template Twig (ex: 'emails/welcome.html.twig')
     * @param array<string, mixed>|null $context Variables à passer au template Twig
     * 
     * @return Email L'objet email configuré (non encore envoyé)
     * 
     * @throws \Twig\Error\Error Si le template est invalide
     */
    public function createTemplateEmail(
        string $senderAddress,
        string $senderName,
        string|array $recipientAddress,
        string $subject,
        string $templatePath,
        ?array $context
    ): Email;

    public function fromConfig(string $type="system"): array;

    public function validatePriority(int $priority): void;
}
