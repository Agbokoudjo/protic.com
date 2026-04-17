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

namespace App\DataTransformer;

use App\Entity\BaseUserInterface;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Gère la conversion entre le champ 'roles' de l'entité (array) et la valeur unique du formulaire (string).
 * Cette méthode permet d'avoir la contrainte métier de **sélection unique** dans l'interface de création/édition,
 *  tout en respectant le format de tableau (`json`) nécessaire pour le fonctionnement de la sécurité Symfony en base de données.
 * 
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
class RoleArrayTransformer implements DataTransformerInterface
{
    public function __construct(private readonly BaseUserInterface $_user)
    {
        if(!$_user instanceof BaseUserInterface){

            throw new \InvalidArgumentException(
                \sprintf('I was expecting a type of %s, but you gave a %s as the type argument',
                    BaseUserInterface::class,\gettype($_user)))
                    ;
        }
    }

    /**
     * Entité -> Formulaire : Convertit l'array BDD en la string unique pour le formulaire.
     */
    public function transform(mixed $value): string
    {
        if (!is_array($value) || empty($value)) {
            return '';
        }

        $BASE_ROLE_TO_EXCLUDE = $this->_user::ROLE_DEFAULT ;

        $principaleRole = array_filter($value, function ($role) use ($BASE_ROLE_TO_EXCLUDE) {
            return is_string($role) && str_starts_with($role, 'ROLE_') && $role !== $BASE_ROLE_TO_EXCLUDE;
        });

        $firstRole = array_key_first($principaleRole);
        return $firstRole !== null ? $principaleRole[$firstRole] : '';
    }

    /**
     * Formulaire -> Entité : Convertit la string du formulaire en l'array BDD.
     */
    public function reverseTransform(mixed $value): array
    {
        if (null === $value || '' === $value) {

            return [$this->_user::ROLE_DEFAULT];
        }

        return array_unique([$value]);
    }
}
