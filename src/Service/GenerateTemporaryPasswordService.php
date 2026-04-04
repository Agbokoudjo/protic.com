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
namespace App\Service;

use App\Security\Generator\PasswordGeneratorInterface;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package App\Application\UseCase\CommandHandler\User
 */
final class GenerateTemporaryPasswordService 
{
     /**
     * Longueur du mot de passe temporaire généré à l'activation.
     */
    private const TEMPORARY_PASSWORD_LENGTH = 20;

    public function __construct(
        private readonly PasswordGeneratorInterface $passwordGenerator
    ) {}

    /**
     * Génère un mot de passe temporaire fort.
     * 
     * Caractéristiques :
     * - Longueur : 20 caractères
     * - Lettres majuscules et minuscules
     * - Chiffres
     * - Caractères spéciaux pour plus de sécurité
     * - Caractères ambigus exclus (0/O, 1/l/I)
     *
     * @return string Le mot de passe en clair
     * 
     * @throws RuntimeException Si la génération échoue
     */
    public function generateTemporaryPassword(): string
    {
        try {
            $password = $this->passwordGenerator->generate(
                self::TEMPORARY_PASSWORD_LENGTH,
                true,  // Inclure symboles
                true   // Exclure caractères ambigus
            ); 
 
            if (empty($password)) {
                throw new \RuntimeException('Le générateur de mot de passe a retourné une chaîne vide.');
            }

            return $password;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Impossible de générer un mot de passe sécurisé: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
