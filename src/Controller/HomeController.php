<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
        '/',
        name: 'app_home',
        methods: ['GET'],
        options: [
            'sitemap' => ['priority' => 1.0, 'changefreq' => 'daily']
        ]
    )
]
final class HomeController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        $response = $this->render('index.html.twig');

        if ($this->getParameter('app.env') === 'prod') {
            $response->setPublic();
           $response->setMaxAge(604800);        
            $response->setSharedMaxAge(604800);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            $cache_tag= md5($this->getParameter('CACHE_VERSION_CONTROLLER') . $response->getContent()) ;
            $response->setEtag($cache_tag);
        }

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }
}
