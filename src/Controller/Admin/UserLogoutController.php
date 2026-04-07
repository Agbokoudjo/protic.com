<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/logout', name: 'sonata_user_admin_security_logout', methods: ['GET'])]
final class UserLogoutController
{
    public function __invoke(Security $security): void
    {
        
    }
}
