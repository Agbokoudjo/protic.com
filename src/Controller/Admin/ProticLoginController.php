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

namespace App\Controller\Admin;

use Sonata\AdminBundle\Admin\Pool;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
#[Route('/admin/form/login', name: 'app_admin_user_login', methods: ['GET', 'POST'])]
final class ProticLoginController extends AbstractController
{

    public function __construct(
        private Pool $adminPool,
        #[Autowire(service: "sonata.admin.global_template_registry")]
        private TemplateRegistryInterface $templateRegistry,
        private ?CsrfTokenManagerInterface $csrfTokenManager = null,
    ) {}

    public function __invoke(AuthenticationUtils $authenticationUtils): Response
    {
        $csrfToken = null;
        if (null !== $this->csrfTokenManager) {
            $csrfToken = $this->csrfTokenManager->getToken('authenticate')->getValue();
        }
        return $this->render('bundles/SonataUserBundle/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'base_template' => $this->templateRegistry->getTemplate('layout'),
            'admin_pool' => $this->adminPool,
            'last_username' => $authenticationUtils->getLastUsername(),
            'csrf_token' => $csrfToken,
        ]);
    }
}
