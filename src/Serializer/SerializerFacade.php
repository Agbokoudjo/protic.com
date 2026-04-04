<?php

declare(strict_types=1);

namespace App\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Facade pour simplifier l'utilisation du serializer Symfony.
 * Encapsule les opérations de normalisation/dénormalisation.
 * 
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
readonly class SerializerFacade
{
    public function __construct(
        public NormalizerInterface $normalizer,
        public DenormalizerInterface $denormalizer
    ) {}

    /**
     * Normalise un objet en tableau.
     *
     * @param mixed $object L'objet à normaliser
     * @param string $format le format
     * @param array<string, mixed> $context Contexte additionnel
     * @return array<string, mixed>|string|int|float|bool|null
     */
    public function normalize(mixed $object, ?string $format=null,array $context = []): mixed
    {
        return $this->normalizer->normalize($object, $format, $context);
    }

    /**
     * Dénormalise des données en objet du type spécifié.
     *
     * @template T
     * @param mixed $data Les données à dénormaliser
     * @param class-string<T> $type Le type de l'objet cible
     * @param array<string, mixed> $context Contexte additionnel
     * @return T
     */
    public function denormalize(mixed $data, string $type, array $context = []): mixed
    {
        return $this->denormalizer->denormalize($data, $type, null, $context);
    }
}
