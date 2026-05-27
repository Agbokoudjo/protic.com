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

namespace App\QueueHandler;

use App\Persistance\UserSessionManagerInterface;
use App\Queue\Message\RevokeInactiveSessionsMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler du message de révocation des sessions inactives.
 *
 * Appelé toutes les 15 minutes par le Scheduler.
 * Délègue la logique métier à UserSessionManagerInterface::cleanExpiredSessions().
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
#[AsMessageHandler(fromTransport: 'scheduler_session_cleanup')]
final class RevokeInactiveSessionsHandler
{
    public function __construct(
        private readonly UserSessionManagerInterface $sessionManager,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(RevokeInactiveSessionsMessage $message): void
    {
        try {
            $revokedSessions = $this->sessionManager->cleanExpiredSessions($message->inactivityHours);
            $count = count($revokedSessions);

            if ($count > 0) {
                $this->logger->info('Sessions inactives révoquées', [
                    'count'             => $count,
                    'inactivity_hours'  => $message->inactivityHours,
                    'revoked_at'        => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ]);
            } else {
                $this->logger->debug('Aucune session inactive à révoquer', [
                    'inactivity_hours' => $message->inactivityHours,
                ]);
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('Erreur lors de la révocation des sessions inactives', [
                'exception_class'   => $throwable::class,
                'exception_message' => $throwable->getMessage(),
                'file'              => $throwable->getFile(),
                'line'              => $throwable->getLine(),
            ]);

            // Re-throw pour que Messenger puisse gérer le retry / DLQ
            throw $throwable;
        }
    }
}
