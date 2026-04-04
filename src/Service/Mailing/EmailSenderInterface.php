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

use App\Service\Mailing\PriorityInterface;
use InvalidArgumentException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Interface pour l'envoi d'emails système automatisés.
 * 
 * Gère l'envoi des notifications système telles que :
 * - Confirmation d'inscription
 * - Réinitialisation de mot de passe
 * - Notifications de compte
 * - Alertes système
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Application\Service\Mailer
 */
interface EmailSenderInterface 
{
    /**
     * Envoie un email système à un utilisateur.
     *
     * @param  string|array $recipientAddress L'adresse email du destinataire
     * @param string $subject L'objet de l'email
     * @param string $templatePath Le chemin vers le template Twig (ex: 'emails/confirmation.html.twig')
     * @param array<string, mixed> $context Variables à passer au template
     * @param int $priority Niveau de priorité (1=max, 5=min). Utiliser les constantes PRIORITY_*
     * 
     * @return void
     * 
     * @throws TransportExceptionInterface Si l'envoi échoue
     * @throws InvalidArgumentException Si l'email du destinataire est invalide ou la priorité invalide
     */
    public function send(
        string|array $recipientAddress,
        string $subject,
        string $templatePath,
        array $context,
        int $priority = PriorityInterface::PRIORITY_NORMAL
    ): void;
}
