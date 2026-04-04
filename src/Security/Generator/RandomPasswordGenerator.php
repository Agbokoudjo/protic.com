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

use App\Security\Generator\PasswordGeneratorInterface;
use InvalidArgumentException;

/**
 * Générateur de mots de passe cryptographiquement sécurisés.
 * 
 * Garantit la génération de mots de passe forts contenant au minimum :
 * - Une lettre majuscule
 * - Une lettre minuscule
 * - Un chiffre
 * - Un symbole spécial
 * 
 * Utilise des fonctions cryptographiquement sécurisées (random_int)
 * pour garantir l'imprévisibilité des mots de passe générés.
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Application\Service
 */
final class RandomPasswordGenerator implements PasswordGeneratorInterface
{
    /**
     * Longueur minimale recommandée pour un mot de passe sécurisé.
     */
    private const MIN_LENGTH = 16;

    /**
     * Lettres majuscules.
     */
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Lettres minuscules.
     */
    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';

    /**
     * Chiffres.
     */
    private const DIGITS = '0123456789';

    /**
     * Symboles et caractères spéciaux.
     */
    private const SYMBOLS = '!@#$%^&*()-_+=~`[]{}\\|:;"\'<>,.?/';

    /**
     * Caractères potentiellement ambigus à exclure si nécessaire.
     */
    private const AMBIGUOUS_CHARS = '0O1lI|`\'";:,./\\';


    // S'assurer qu'un caractère de chaque type est présent
    private const REQUIRED_SETS = [
        self::UPPERCASE,
        self::LOWERCASE,
        self::DIGITS,
        self::SYMBOLS,
    ];

    /**
     * @throws InvalidArgumentException Si la longueur demandée est insuffisante.
     */
    public function generate(
        int $length = self::MIN_LENGTH,
        bool $includeSymbols = true,
        bool $excludeAmbiguous = false): string
    {
        $this->validateLength($length);

        $sets = [
            self::UPPERCASE,
            self::LOWERCASE,
            self::DIGITS,
        ];

        if ($includeSymbols) {
            $sets[] = self::SYMBOLS;
        }

        $allChars = implode('', $sets);

        if ($excludeAmbiguous) {
            $allChars = str_replace(str_split(self::AMBIGUOUS_CHARS), '', $allChars);
        }

        $password = [];

        // 1. Garantir au moins un caractère de chaque type requis
        foreach (self::REQUIRED_SETS as $set) {
            $password[] = $this->getRandomCharFromSet($set);
        }

        // 2. Compléter avec des caractères aléatoires
        $remainingLength = $length - count(self::REQUIRED_SETS);
        for ($i = 0; $i < $remainingLength; $i++) {
            $password[] = $this->getRandomCharFromSet($allChars);
        }

        // 3. Mélanger de manière cryptographiquement sécurisée
        return $this->secureshuffle($password);
    }


    /**
     * Récupère un caractère aléatoire d'un ensemble donné de manière sécurisée.
     *
     * Utilise random_int() qui est cryptographiquement sécurisé,
     * contrairement à rand() ou mt_rand().
     *
     * @param string $set Ensemble de caractères (ex: 'ABCD', '0123456789')
     * 
     * @return string Un caractère unique sélectionné aléatoirement
     * 
     * @throws \Exception Si random_int() échoue (source d'aléa insuffisante)
     * 
     * @example //utilisation
     *  * $char = $this->getRandomCharFromSet('0123456789'); // Retourne '5', '0', '9', etc.
     *  * $char = $this->getRandomCharFromSet('!@#$%^&*');   // Retourne '@', '&', '#', etc.
     */
    private function getRandomCharFromSet(string $set): string
    {
        $setLength = strlen($set);

        if ($setLength === 0) {
            throw new InvalidArgumentException('L\'ensemble de caractères ne peut pas être vide');
        }

        $randomIndex = random_int(0, $setLength - 1);

        return $set[$randomIndex];
    }


    /**
     * Mélange un tableau de caractères de manière cryptographiquement sécurisée.
     * 
     * Utilise l'algorithme de Fisher-Yates avec random_int() au lieu de
     * str_shuffle() qui n'est pas cryptographiquement sécurisé.
     *
     * @param array<int, string> $characters Tableau de caractères à mélanger
     * 
     * @return string La chaîne de caractères mélangée
     * 
     * @throws \Exception Si random_int() échoue
     */
    private function secureshuffle(array $characters): string
    {
        $count = count($characters);
        $tmp_char='';
        // Algorithme de Fisher-Yates (shuffle cryptographiquement sécurisé)
        for ($i = $count - 1; $i > 0; $i--) {
            $j = random_int(0, $i);

            // Échange des éléments
            $tmp_char= $characters[$i];
            [$characters[$i], $characters[$j]] = [$characters[$j], $tmp_char];
        }

        return implode('', $characters);
    }

    /**
     * Valide que la longueur du mot de passe est suffisante.
     *
     * @param int $length La longueur demandée
     * 
     * @throws InvalidArgumentException Si la longueur est insuffisante
     */
    private function validateLength(int $length): void
    {
        if ($length < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'La longueur du mot de passe doit être d\'au moins %d caractères. Reçu: %d',
                    self::MIN_LENGTH,
                    $length
                )
            );
        }
    }
}