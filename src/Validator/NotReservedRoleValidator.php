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

namespace App\Validator;

use App\Validator\NotReservedRole;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
class NotReservedRoleValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotReservedRole) {
            throw new UnexpectedTypeException($constraint, NotReservedRole::class);
        }

        // La valeur doit être une chaîne
        if (null === $value || '' === $value) {
            return;
        }

        // Normalisation (souvent bonne pratique)
        $value = strtoupper((string) $value);

        // VÉRIFICATION DE L'EXCLUSION
        if (in_array($value, $constraint->reservedRoles, true)) {
            $this->context->buildViolation($constraint->translation_message_id)
                ->setParameter('{{ value }}', $value)
                ->setTranslationDomain($constraint->translation_message_domain)
                ->addViolation();
        }
    }
}