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

namespace App\CommandHandler;

use App\Persistance\UserManagerInterface;
use App\Service\CanonicalFieldsUpdaterInterface;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
final readonly class UpdateUserProfileHandler
{
    public function __construct(
        private UserManagerInterface $manager,
        private CanonicalFieldsUpdaterInterface $canonicalFields
    ) {}

    public function handle(string|int $id): void
    {
        $user = $this->manager->find($id);

        if (null === $user) { return;}

        $this->canonicalFields->updateCanonicalFields($user);

        $slug_hash = md5(\sprintf('%s_%d', $user->getUsernameCanonical(), time()));
        $user->setSlug(sha1($slug_hash)); 

        $this->manager->save($user);
    }
}
