<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\TeamMemberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/about', 
    name: 'app_about',
    methods: ['GET'],
    options: [
        'sitemap' => ['priority' => 0.7, 'changefreq' => 'daily']
    ]
)]
final class AboutController extends AbstractController
{
    public function __invoke(TeamMemberRepository $repo,Request $request): Response
    {
        /* ── Valeurs de l'entreprise ── */
        $values = [
            ['icon' => 'bi-star-fill',         'title' => 'Excellence',       'text' => 'Chaque livre que nous publions reflète notre exigence de qualité, de la conception graphique à l\'impression finale.'],
            ['icon' => 'bi-heart-fill',         'title' => 'Passion',          'text' => 'Nous croyons au pouvoir transformateur du livre. Notre engagement pour la culture africaine guide chacune de nos décisions.'],
            ['icon' => 'bi-people-fill',        'title' => 'Proximité',        'text' => 'Nous accompagnons chaque auteur personnellement, de la soumission du manuscrit jusqu\'à la distribution finale.'],
            ['icon' => 'bi-shield-check-fill',  'title' => 'Intégrité',        'text' => 'Transparence dans nos tarifs, respect des délais et protection des droits d\'auteur sont nos engagements fondamentaux.'],
            ['icon' => 'bi-globe-americas',     'title' => 'Ouverture',        'text' => 'Nous valorisons les auteurs béninois et africains, qu\'ils soient au Bénin ou dans la diaspora mondiale.'],
            ['icon' => 'bi-lightbulb-fill',     'title' => 'Innovation',       'text' => 'Nous adoptons les technologies modernes pour améliorer continuellement nos processus de publication et de distribution.'],
        ];

        /* ── Jalons historiques ── */
        $milestones = [
            ['year' => '2010', 'title' => 'Fondation de ProTIC',         'text' => 'Création de ProTIC Editions & Services par M. SETONWAN DENIS HOUNGNIMON à Abomey-Calavi.'],
            ['year' => '2012', 'title' => 'Adhésion APPEL-Bénin',        'text' => 'ProTIC rejoint l\'Association Professionnelle des Éditeurs de Livres du Bénin, renforçant son réseau.'],
            ['year' => '2015', 'title' => '50 livres publiés',            'text' => 'Franchissement du cap symbolique de 50 livres publiés et de 40 auteurs accompagnés.'],
            ['year' => '2018', 'title' => 'Expansion sous-régionale',    'text' => 'Début de la distribution dans les pays voisins : Togo, Niger et Côte d\'Ivoire.'],
            ['year' => '2022', 'title' => '100+ livres au catalogue',    'text' => 'Le catalogue dépasse les 100 titres et 70 auteurs. Lancement du processus de soumission en ligne.'],
            ['year' => '2026', 'title' => 'Plateforme numérique',        'text' => 'Lancement du site web moderne ProTIC avec catalogue interactif, soumission de manuscrits en ligne et espace auteurs.'],
        ];

        $team = $repo->findVisibleOrderedByPosition();

        $response =$this->render('about/index.html.twig', [
            'team'       => $team,
            'values'     => $values,
            'milestones' => $milestones,
        ]);

        if ($this->getParameter('app.env') === 'prod') {
            $hashContent= md5($this->getParameter('CACHE_VERSION_CONTROLLER') . $response->getContent());
            $tag=sprintf("%s_%d",$hashContent,count($team)) ;
            $response->setEtag($tag);
            $response->setPublic();
            $response->setMaxAge(604800);        
            $response->setSharedMaxAge(604800);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            if ($response->isNotModified($request)) {
                return $response;
            }
        }

        return $response;
    }
}
