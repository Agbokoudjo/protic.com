<?php
declare(strict_types=1);
/*
 * src/Controller/BookShowController.php
 *
 * Route publique : GET /livre/{slug}
 * Affiche la page dédiée d'un livre avec toutes les méta OG/SEO.
 * Pas de firewall, lecture seule.
 */

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[Route(
    path: '/book/{slug}',
    name: 'app_book_show',
    requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'],
    methods: ['GET'],
)]
final class BookShowController extends AbstractController
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly UploaderHelper $uploaderHelperService
    ) {}

    public function __invoke(Request $request, string $slug): Response
    {
        if (\strlen($slug) > 255) {
            throw $this->createNotFoundException('Livre introuvable.');
        }
       
        $book = $this->bookRepository->findOneBySlug($slug);
        if ($book === null) {
            throw $this->createNotFoundException(
                \sprintf('Le livre « %s » est introuvable.', $slug)
            );
        }

        // ── Construction des données SEO ─────────────────────────────
        $authorName  = $book->getAuthor()?->getFullName() ?? 'Auteur inconnu';
        $categoryName = $book->getCategory()?->getName() ?? '';
        $year = $book->getPublishedAt()
            ? $book->getPublishedAt()->format('Y')
            : null;

        // Résumé court pour les méta (160 caractères max)
        $summary     = $book->getSummary() ?? '';
        $metaDesc    = \mb_strlen($summary) > 160
            ? \mb_substr($summary, 0, 157) . '…'
            : $summary;

        // URL absolue de la couverture pour OG:image
        $coverUrl = null;
        if ($book->getCoverImage()) {
            $coverUrl = $request->getSchemeAndHttpHost()
                . '/'
                . $this->uploaderHelperService->asset($book, "coverFile", Book::class)
              ;
        }

        $canonicalUrl =$this->generateUrl('app_book_show', ['slug' => $slug]);

        $response = new Response();
        $authorDate = $book->getAuthor()?->getUpdatedAt();
        $bookDate   = $book->getUpdatedAt();
        $dataLastModified = match (true) {
            $authorDate !== null && $bookDate !== null => max($authorDate, $bookDate),
            default => $authorDate ?? $bookDate ?? $book->getPublishedAt(),
        };
        
        $response->setLastModified($dataLastModified);
        $response->setPublic();
        $response->setMaxAge(604800);
        $response->setSharedMaxAge(604800);

        if ($this->getParameter('app.env') === 'prod' && $response->isNotModified($request)) {
            return $response;
        }

        return $this->render('book/show.html.twig', [
            'book'          => $book,
            'author_name'   => $authorName,
            'category_name' => $categoryName,
            'year'          => $year,
            'meta_desc'     => $metaDesc,
            'cover_url'     => $coverUrl,
            'author'=> $book->getAuthor(),
            'canonical_url' => $canonicalUrl,
            'og_title'      => \sprintf('%s — %s | ProTIC Éditions', $book->getTitle(), $authorName),
        ], $response);
    }
}
