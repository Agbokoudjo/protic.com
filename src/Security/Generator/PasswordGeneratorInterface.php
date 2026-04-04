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

namespace App\Security\Generator;

/**
 * Définit le contrat pour la génération de mots de passe aléatoires et sécurisés.
 */
interface PasswordGeneratorInterface
{
    /**
     * Génère un mot de passe aléatoire respectant les critères de complexité.
     *
     * @param int $length La longueur minimale requise (16).
     */
    public function generate(int $length,bool $includeSymbols=true,bool $excludeAmbiguous=false): string;
}