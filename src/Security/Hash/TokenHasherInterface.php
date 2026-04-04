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

namespace App\Security\Hash;

/**
 * Interface pour le hachage sécurisé de tokens à usage unique.
 * 
 * Définit le contrat pour hasher et vérifier des tokens utilisés pour :
 * - Confirmation d'email
 * - Réinitialisation de mot de passe
 * - Tokens de session temporaires
 * - Tokens CSRF
 * 
 * Utilise des algorithmes de hachage lents (bcrypt, argon2) pour
 * protéger contre les attaques par force brute.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Domain\User\Service\Security
 */
interface TokenHasherInterface
{
    /**
     * Hashe un token en clair pour le stockage sécurisé en base de données.
     * 
     * Le hash généré doit être résistant aux attaques par :
     * - Force brute (algorithme lent)
     * - Rainbow tables (salt automatique)
     * - Timing attacks (comparaison à temps constant)
     *
     * @param string $plainToken Le token en clair à hasher
     * 
     * @return string Le hash du token, incluant le salt et les paramètres de l'algorithme
     * 
     * @throws \RuntimeException Si le hachage échoue (mémoire insuffisante, etc.)
     */
    public function hash(string $plainToken): string;

    /**
     * Vérifie si un token en clair correspond au hash stocké.
     * 
     * Utilise une comparaison à temps constant pour éviter les timing attacks.
     * Compatible avec les anciens hashs si l'algorithme change (rehashing automatique).
     *
     * @param string $plainToken Le token en clair à vérifier
     * @param string $hashedToken Le hash stocké en base de données
     * 
     * @return bool True si le token correspond, false sinon
     */
    public function verify(string $plainToken, string $hashedToken): bool;

    /**
     * Vérifie si un hash doit être re-hashé avec des paramètres plus récents.
     * 
     * Utile pour migrer progressivement vers des algorithmes plus sécurisés
     * sans invalider les tokens existants.
     *
     * @param string $hashedToken Le hash à vérifier
     * 
     * @return bool True si le hash doit être régénéré, false sinon
     */
    public function needsRehash(string $hashedToken): bool;
}
