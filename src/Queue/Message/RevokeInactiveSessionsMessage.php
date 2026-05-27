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
 * Message Symfony Messenger déclenché périodiquement par le Scheduler
 * pour nettoyer les sessions inactives.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
final class RevokeInactiveSessionsMessage
{
    public function __construct(
        /** Seuil d'inactivité en heures avant révocation. */
        public readonly int $inactivityHours = 2,
    ) {}
}
