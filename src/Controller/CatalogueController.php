<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/catalogue',
    name: 'app_catalogue',
    methods: ['GET'],
    options: [
        'sitemap' => ['priority' => 0.9, 'changefreq' => 'daily']
    ]
)]
final class CatalogueController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        $response = $this->render('catalogue/index.html.twig');

        if ($this->getParameter('app.env') === 'prod') {
            $cache_ttl_public= $this->getParameter('CACHE_TTL_PUBLIC') ?? 3600 ;
            $response->setPublic();
            $response->setMaxAge($cache_ttl_public);
            $response->setSharedMaxAge($cache_ttl_public);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            $cache_tag = md5($this->getParameter('CACHE_VERSION_CONTROLLER') . $response->getContent());
            $response->setEtag($cache_tag);
        }

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }
}
