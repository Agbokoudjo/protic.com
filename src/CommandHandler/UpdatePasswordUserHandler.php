<?php

declare(strict_types=1);

namespace App\CommandHandler;

use App\Persistance\UserManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Gère la mise à jour sécurisée du mot de passe avec traçabilité.
 * * @author AGBOKOUDJO Franck
 */
final readonly class UpdatePasswordUserHandler
{
    public function __construct(
        private UserManagerInterface $userManager,
        private LoggerInterface $logger, // Injection du logger PSR-3
    ) {}

    public function handle(
        string|int $userId,
        string $plainPassword
    ): void {
        try {
            $user = $this->userManager->find($userId);

            if (null === $user) {
                $this->logger->warning('Tentative de changement de mot de passe pour un utilisateur inexistant.', [
                    'user_id' => $userId
                ]);
                return;
            }

            // Mise à jour du mot de passe
            $user->setPlainPassword($plainPassword);
            $this->userManager->updatePassword($user);

            // On force le rafraîchissement des métadonnées (sel, updatedAt, etc.)
            $user->preUpdate();
            $this->userManager->save($user);

            // LOG DE SUCCÈS : On trace l'action (sans jamais loguer le password !)
            $this->logger->info('Mot de passe mis à jour avec succès.', [
                'user_id' => $user->getId(),
                'username' => $user->getUsername(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (\Throwable $th) {
            // LOG D'ERREUR : On trace l'exception pour le débug
            $this->logger->error('Échec de la mise à jour du mot de passe.', [
                'user_id' => $userId,
                'error' => $th->getMessage()
            ]);

            throw $th;
        }
    }
}
