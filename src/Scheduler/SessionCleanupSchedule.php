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

namespace App\Scheduler;

use App\Queue\Message\RevokeInactiveSessionsMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Planificateur de nettoyage des sessions inactives.
 *
 * Toutes les 15 minutes → révoque les sessions sans activité depuis 2 heures.
 *
 * Nécessite symfony/scheduler (inclus dans Symfony 6.3+).
 * FrankenPHP exécute le Scheduler nativement en mode worker — aucun cron externe nécessaire.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
#[AsSchedule('session_cleanup')]
final class SessionCleanupSchedule implements ScheduleProviderInterface
{
    private ?Schedule $schedule = null;

     public function __construct(
        private readonly CacheInterface $cache, // cache.app ou cache Redis
    ) {}
    
    public function getSchedule(): Schedule
    {
        return $this->schedule ??= (new Schedule())
            ->with(
                RecurringMessage::every(
                '1 hour',
                    new RevokeInactiveSessionsMessage(inactivityHours: 2)
                )
            )
            ->stateful($this->cache)           // mémorise le dernier run
            ->processOnlyLastMissedRun(true);  // si worker arrêté, exécute 1 seul rattrapage
    }
}
