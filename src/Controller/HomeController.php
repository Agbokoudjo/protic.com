<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function __invoke(): Response
    {
        return $this->render('index.html.twig');
    }
}
