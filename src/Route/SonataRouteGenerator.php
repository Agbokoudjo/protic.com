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

namespace App\Route;

use App\Security\Encryption\IdEncryptionInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Route\DefaultRouteGenerator;
use Sonata\AdminBundle\Route\RouteGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 *RouteGenerator  avec chiffrement automatique des IDs
 * 
 * Tous les liens (show, edit, delete, etc.) vont automatiquement
 * chiffrer l'ID en URL
 * 
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
final class SonataRouteGenerator  implements RouteGeneratorInterface
{
    public function __construct(
        private readonly DefaultRouteGenerator $defaultRouteGenerator,
        private readonly IdEncryptionInterface $encryptionService
        ){}

    public function generate(string $name, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $parameters = $this->getParameterIdEncryption('id', $parameters);

        return $this->defaultRouteGenerator->generate($name, $parameters, $referenceType);
    }

     public function generateUrl(
        AdminInterface $admin,
        string $name,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string {
        $parameters = $this->getParameterIdEncryption($admin->getIdParameter(), $parameters);
        return $this->defaultRouteGenerator->generateUrl($admin, $name, $parameters, $referenceType);
    }

    public function generateMenuUrl(
        AdminInterface $admin,
        string $name,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): array {

       $parameters = $this->getParameterIdEncryption($admin->getIdParameter(),$parameters);
       return $this->defaultRouteGenerator->generateMenuUrl($admin,$name,$parameters,$referenceType);
    }

    private function getParameterIdEncryption(string $idParameter,array $parameters = []):array{
        // Si le paramètre 'id' existe, le chiffrer
        if (isset($parameters[$idParameter]) && !empty($parameters[$idParameter])) {
            $parameters[$idParameter] = $this->encryptionService->encryptId($parameters[$idParameter]);
        }
        return $parameters ;
    }

    public function hasAdminRoute(AdminInterface $admin, string $name): bool
    {
        return $this->defaultRouteGenerator->hasAdminRoute($admin, $name);
    }
}
