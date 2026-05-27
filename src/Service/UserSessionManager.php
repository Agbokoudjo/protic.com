<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\UserSession;
use App\Persistance\UserSessionManagerInterface;
use App\Repository\UserSessionRepository;

/**
 * Orchestre les opérations de session entre la BDD (Repository)
 * et Redis (session store).
 *
 * Le Repository ne connaît que Doctrine.
 * Ce service coordonne les deux infrastructures.
 */
final class UserSessionManager implements UserSessionManagerInterface
{
    private const REDIS_SESSION_PREFIX = 'orind_sess:';

    public function __construct(
        private readonly UserSessionRepository $repository,
        private readonly \Redis $redis,
    ) {}

    public function createSession(
        string $userIdentifier,
        string $sessionId,
        ?string $ipAddress         = null,
        ?string $userAgent         = null,
        ?string $deviceFingerprint = null,
    ): UserSession {
        // Révoquer les sessions actives (BDD + Redis)
        $this->revokeAllActiveSessions($userIdentifier);

        return $this->repository->createSession(
            $userIdentifier,
            $sessionId,
            $ipAddress,
            $userAgent,
            $deviceFingerprint,
        );
    }

    public function getSessionInfo(string $userIdentifier): ?array
    {
        return [];
    }

    public function revokeAllActiveSessions(string $userIdentifier): void
    {
        $revokedSessionIds = $this->repository->revokeAllActiveSessions($userIdentifier);

        foreach ($revokedSessionIds as $sessionId) {
            $this->redis->unlink(self::REDIS_SESSION_PREFIX . $sessionId);
        }
    }

    public function removeSession(string $sessionId): void
    {
        $revokedSessionId = $this->repository->removeSession($sessionId);

        if ($revokedSessionId !== null) {
            $this->redis->unlink(self::REDIS_SESSION_PREFIX . $revokedSessionId);
        }
    }

    public function updateSessionActivity(string $sessionId): void
    {
        $this->repository->updateSessionActivity($sessionId);
    }

    public function cleanExpiredSessions(int $hoursOfInactivity = 2): array
    {
        $expired = $this->repository->markExpiredSessionsAsInactive($hoursOfInactivity);

        foreach ($expired as $session) {
            $this->redis->unlink(self::REDIS_SESSION_PREFIX . $session->getSessionId());
        }

        return $expired;
    }

    public function hasActiveSession(string $userIdentifier, string $currentSessionId): bool
    {
        return $this->repository->hasActiveSession($userIdentifier, $currentSessionId);
    }

    public function findActiveSession(string $userIdentifier): ?UserSession
    {
        return $this->repository->findActiveSession($userIdentifier);
    }
}
