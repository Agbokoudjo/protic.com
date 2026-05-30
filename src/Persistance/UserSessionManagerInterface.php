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

namespace App\Persistance;

use App\Domain\BaseUserInterface;
use App\Domain\UserSessionInterface;

/**
 * Contrat du service de gestion des sessions utilisateur uniques.
 *
 * Toutes les méthodes qui mutent l'état (create, update, remove) sont
 * dispatchables de manière asynchrone via AsyncMethodDispatcherInterface.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
interface UserSessionManagerInterface
{
    /**
     * Crée une session pour l'utilisateur identifié et supprime toutes
     * ses sessions précédentes (politique single-session).
     *
     * @param string $userIdentifier  Identifiant métier de l'utilisateur
     * @param string      $sessionId       ID de session Symfony (session->getId())
     * @param string|null $ipAddress       IP du client
     * @param string|null $userAgent       User-Agent HTTP
     * @param string|null $deviceFingerprint Hash appareil (optionnel)
     */
    public function createSession(
        string $userIdentifier,
        string $sessionId,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $deviceFingerprint = null,
    ): UserSessionInterface;

    /**
     * Retourne la session active la plus récente d'un utilisateur,
     * ou null s'il n'en a pas.
     */
    public function findActiveSession(string $userIdentifier): ?UserSessionInterface;

    /**
     * Indique si l'utilisateur possède une session active dont l'ID
     * est DIFFÉRENT de $currentSessionId (= conflit de session).
     *
     * Retourne true  → conflit détecté, redirection nécessaire.
     * Retourne false → session courante valide ou pas de session en base.
     */
    public function hasActiveSession(string $userIdentifier, string $currentSessionId): bool;

    /**
     * Retourne les métadonnées de la session active de l'utilisateur
     * sous forme de tableau normalisé, ou null si aucune session.
     */
    public function getSessionInfo(string $userIdentifier): ?array;

    /**
     * Révoque (soft-delete) toutes les sessions inactives depuis plus
     * de $hoursOfInactivity heures.
     *
     * Appelé périodiquement par le Scheduler (toutes les 15 min).
     *
     * @return  array Les sessions révoquées
     */
    public function cleanExpiredSessions(int $hoursOfInactivity = 2): array;

    /**
     * Révoque toutes les sessions actives d'un utilisateur.
     * Utilisé aussi lors de la déconnexion explicite.
     */
    public function revokeAllActiveSessions(string $userIdentifier): void;

    /**
     * Supprime une session spécifique par son ID de session PHP.
     * Appelé lors de la déconnexion (LogoutSubscriber).
     */
    public function removeSession(string $sessionId): void;

    /**
     * Met à jour le timestamp de dernière activité de la session.
     *
     * Utilise un verrou cache pour limiter les écritures SQL à une fois
     * par heure maximum (évite un flush à chaque requête HTTP).
     */
    public function updateSessionActivity(string $sessionId): void;
}
