<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function __invoke(): Response
    {
        return $this->render('catalogue/index.html.twig');
    }
}
