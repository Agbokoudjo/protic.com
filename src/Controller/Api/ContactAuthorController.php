<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\CommandHandler\AuthorCommandNotificationHandler;
use App\Entity\Book;
use App\Entity\ContactRequest;
use App\QueueHandler\AsyncMethodDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('api/contact-author', name: 'api_contact_author', methods: ['POST'])]
final class ContactAuthorController extends AbstractController
{
    public function __construct(
        private readonly DenormalizerInterface  $denormalizer,
        private readonly ValidatorInterface     $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly AsyncMethodDispatcher $asyncMethodDispatcher,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $isJson = str_contains($request->headers->get('Content-Type', ''), 'application/json');
        $raw    = $isJson
            ? json_decode($request->getContent(), true) ?? []
            : $request->request->all();

        if (!is_array($raw) || empty($raw)) {
            return $this->json(['error' => 'Corps de la requete HTTP est invalide.'], 400);
        }

        /* Parse du numéro de téléphone ─────────────────────
           Le dénormaliseur ne sait pas convertir string → PhoneNumber.
           On le fait manuellement AVANT la dénormalisation.          */
        $phoneStr = trim($raw['phone'] ?? '');
        if ($phoneStr === '') {
            return $this->json(
                ['phone' => 'Le numéro de téléphone est obligatoire.'],
                422
            );
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $phoneObject      = $phoneUtil->parse($phoneStr);
            $raw['phone']     = $phoneUtil->format($phoneObject, PhoneNumberFormat::E164);
        } catch (NumberParseException $e) {
            return $this->json(
                ['phone' => 'Numéro de téléphone invalide.'],
                422
            );
        }

        /* ─ Résolution du Book (optionnel) ───────────────────
           On retire bookId du tableau avant la dénormalisation
           pour éviter que le dénormaliseur tente de l'hydrater      */
        $bookId = (int) ($raw['bookId'] ?? 1);
        unset($raw['bookId']);

        $book = null;
        if ($bookId > 0) {
            $book = $this->entityManager->getRepository(Book::class)->find($bookId);
        }
       
        if (!$book) {
            return $this->json(['errors' => 'Livre introuvable.'], 404);
        }

        $countryAlpha2 = $raw['country'] ?? null;

        if ($countryAlpha2 && strlen($countryAlpha2) === 2) {
            try {
                // Convertit 'BJ' en 'BEN', 'FR' en 'FRA', etc.
                $countryAlpha3 = Countries::getAlpha3Code($countryAlpha2);
                $raw['country'] = $countryAlpha3;
            } catch (\Exception $e) {
                // Si le code est invalide, on laisse la validation de l'entité gérer l'erreur
            }
        }
        
        try {
            /** @var ContactRequest $contactRequest */
            $contactRequest = $this->denormalizer->denormalize(
                $raw,
                ContactRequest::class,
                'array',                      
                ['groups' => ['contact_request:write']]
            );
        } catch (\Throwable $e) {
            return $this->json(
                ['errors' => 'Données invalides : ' . $e->getMessage()],
                400
            );
        }

        /* ── Hydratation manuelle des champs non dénormalisables ─ */
        $contactRequest->setPhone($phoneObject);
        $contactRequest->setBook($book);
        $contactRequest->setStatus('pending');
        $contactRequest->setSentAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));

        /* ── Validation via les contraintes de l'entity ─────── */
        $violations = $this->validator->validate($contactRequest);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                /* Convertit le propertyPath "fullName" → clé lisible */
                $field          = $v->getPropertyPath();
                $errors[$field] = $v->getMessage();
            }
            return $this->json($errors, 422);
        }

        /* ── Persistance ─────────────────────────────────────── */
        //$this->entityManager->getRepository(Book::class)->add($contactRequest);

        /* ── Envoi email via SupportMailer via en async  ───────────────────── */
         $this->asyncMethodDispatcher->dispatch(
                AuthorCommandNotificationHandler::class,
                'handle',
                [$contactRequest->getId()]
            ) ;

        return $this->json([
            'success' => true,
            'message' => 'Votre demande a bien été envoyée. L\'auteur vous contactera prochainement.'
        ], 201);
    }
}
