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

use Symfony\Component\Validator\Constraint;

/**
 * @Attibute
 * @Target({"PROPERTY", "METHOD", "CLASS"})
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
class NotReservedRole extends Constraint
{
    public function __construct(
        public readonly array $reservedRoles= [
            'ROLE_SUPER_ADMIN',
            'ROLE_ADMIN',
            'ROLE_USER',
            'ROLE_FOUNDER',
            'ROLE_COFOUNDER'
        ],
        public readonly string $translation_message_id= "reservedRoles",
        public readonly string $translation_message_domain= "validation_errors",
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null)
    {
        parent::__construct($options,$groups,$payload);
    }
    
}