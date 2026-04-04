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

namespace App\Queue\Message;

/**
 * Classe ServiceMethodMessage
 *
 * Représente un message envoyé dans le bus de messages pour exécuter de manière asynchrone
 * une méthode spécifique d’un service.
 *
 * Ce message contient toutes les informations nécessaires à l’exécution :
 *  - le nom du service à invoquer,
 *  - la méthode de ce service à appeler,
 *  - les paramètres à transmettre à la méthode.
 *
 * Il est typiquement utilisé avec le composant Messenger de Symfony pour différer
 * ou déléguer une exécution dans un worker ou une file de traitement.
 *
 * Exemple d’utilisation :
 * ```php
 * $message = new ServiceMethodMessage(UserNotifier::class, 'sendWelcomeEmail', ['userId' => 42]);
 * $bus->dispatch($message);
 * ```
 *
 * Le handler associé récupère ce message et invoque dynamiquement la méthode correspondante.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
final readonly class ServiceMethodMessage
{
    /**
     * @param string $serviceName Nom complet (FQCN) du service à invoquer.
     * @param string $method Nom de la méthode du service à exécuter.
     * @param array $params Paramètres à transmettre à la méthode appelée.
     */
    public function __construct(
        private string $serviceName,
        private string $method,
        private array $params = [],
    ) {}

    /** Retourne le nom complet (FQCN) du service à invoquer. */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /** Retourne le nom de la méthode du service à exécuter. */
    public function getMethod(): string
    {
        return $this->method;
    }

    /** Retourne les paramètres à passer à la méthode appelée. */
    public function getParams(): array
    {
        return $this->params;
    }
}
