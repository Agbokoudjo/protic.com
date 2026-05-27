<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\ContactRequest;
use App\Entity\Faq;
use App\Entity\ManuscriptSubmission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Exposes a single aggregated stats endpoint for the admin dashboard.
 *
 * One HTTP request → 6 COUNT(*) SQL queries → one JSON response.
 * Avoids the N-full-collection-fetch anti-pattern of hitting each
 * API Platform collection endpoint just to read hydra:totalItems.
 *
 * Route : GET /api/admin/dashboard/stats
 * Access: ROLE_ADMIN
 */
#[Route('/api/admin/dashboard', name: 'api_admin_dashboard_')]
#[IsGranted('ROLE_ADMIN')]
final class DashboardStatsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    /**
     * Returns all dashboard counts in a single response.
     *
     * Response shape:
     * {
     *   "authors":                 <int>,
     *   "books":                   <int>,
     *   "categories":              <int>,
     *   "contact_requests":        <int>,
     *   "manuscript_submissions":  <int>,
     *   "faqs":                    <int>
     * }
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        return $this->json([
            'authors'                => $this->count(Author::class),
            'books'                  => $this->count(Book::class),
            'categories'             => $this->count(Category::class),
            'contact_requests'       => $this->count(ContactRequest::class),
            'manuscript_submissions' => $this->count(ManuscriptSubmission::class),
            'faqs'                   => $this->count(Faq::class),
        ]);
    }

    /**
     * Runs a single COUNT(*) DQL query for the given entity class.
     * Uses getSingleScalarResult() — returns one integer, zero ORM hydration.
     */
    private function count(string $entityClass): int
    {
        return (int) $this->em
            ->createQuery("SELECT COUNT(e.id) FROM {$entityClass} e")
            ->getSingleScalarResult();
    }
}
