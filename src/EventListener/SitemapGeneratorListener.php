<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <franckagbokoudjo301@gmail.com>
 * Phone: +229 0167 25 18 86
 * LinkedIn: https://www.linkedin.com/in/internationales-web-services-120520193/
 * Github: https://github.com/Agbokoudjo/norldfinance.com
 * Company: INTERNATIONALES WEB SERVICES
 *
 * For more information, please feel free to contact the author.
 */

namespace App\EventListener;


use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use App\Repository\CategoryRepository;

#[AsEventListener(event: SitemapPopulateEvent::class)]
final class SitemapGeneratorListener
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CategoryRepository    $categoryRepository,
    ) {}


    public function __invoke(SitemapPopulateEvent $event): void
    {
        $this->addStaticPages($event->getUrlContainer());
        //$this->addCategories($event->getUrlContainer());
    }

    private function addStaticPages(UrlContainerInterface $urls): void
    {
        /**
         * @var \ArrayIterator<Route>
         */
        $routesList = $this->router->getRouteCollection()->getIterator();
        
        while ($routesList->valid()) {
            /**
             * @var Route
             */
            $route = $routesList->current();
            /**
             * @var string
             */
            $routeName = $routesList->key();
            /**
             * @var  array<string ,mixed>
             */
            $options_route = $route->getOptions();
            if (!isset($options_route['sitemap'])) {
                $routesList->next();
                continue;
            }

            
            try {
                $url = $this->urlGenerator->generate($routeName, [], UrlGeneratorInterface::ABSOLUTE_URL);
                $urls->addUrl(new UrlConcrete(
                        $url,
                        new \DateTime(),
                        $options_route['sitemap']['changefreq'] ?? UrlConcrete::CHANGEFREQ_WEEKLY,
                        $options_route['sitemap']['priority'] ?? 0.5
                    ), 
                    'static'
                    );
            } catch (\Exception $e) {
                continue;
            }
            
            $routesList->next();
        }
    }

    /*private function addBooks(UrlContainerInterface $urls): void
    {
        foreach ($this->bookRepository->findPublishedForSitemap() as $book) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'app_book_show',
                        ['slug' => $book->getSlug()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    $book->getUpdatedAt() ?? new \DateTimeImmutable(),
                    UrlConcrete::CHANGEFREQ_WEEKLY,
                    0.8,
                ),
                'books'
            );
        }
    }


     private function addAuthors(UrlContainerInterface $urls): void
    {
        foreach ($this->authorRepository->findAllForSitemap() as $author) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'app_author_show',
                        ['id' => $author->getId()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    new \DateTimeImmutable(),
                    UrlConcrete::CHANGEFREQ_MONTHLY,
                    0.6,
                ),
                'authors'
            );
        }
    }*/

    private function addCategories(UrlContainerInterface $urls): void
    {
        foreach ($this->categoryRepository->findAllForSitemap() as $category) {
            $urls->addUrl(
                new UrlConcrete(
                    $this->urlGenerator->generate(
                        'app_catalogue',
                        ['category' => $category->getName()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ),
                    new \DateTimeImmutable(),
                    UrlConcrete::CHANGEFREQ_WEEKLY,
                    0.7,
                ),
                'categories'
            );
        }
    }
}
