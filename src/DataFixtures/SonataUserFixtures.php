<?php

declare(strict_types=1);
/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * Company: INTERNATIONALES WEB APPS & SERVICES
 */

namespace App\DataFixtures;

use App\Entity\SonataUser;
use App\Service\CanonicalFieldsUpdaterInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SonataUserFixtures extends Fixture implements FixtureGroupInterface
{
    public const USER_REFERENCE_PREFIX = 'sonata_user_';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PhoneNumberUtil             $phoneNumberUtil,
        private CanonicalFieldsUpdaterInterface $canonicalFieldsUpdater,
        private readonly string                      $projectDir,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $jsonPath = __DIR__ . '/data/sonata_users.json';

        if (!file_exists($jsonPath)) {
            throw new \RuntimeException(sprintf('Fichier introuvable : %s', $jsonPath));
        }

        $data = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        // Dossier temporaire pour les avatars téléchargés
        $tmpDir = $this->projectDir . '/var/fixtures_tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $loaded = 0;

        foreach ($data as $index => $item) {
            $user = new SonataUser();

            // ── Identité ──────────────────────────────────────
            $username = $item['username'];
            $user->setUsername($username);
            $user->setUsernameCanonical(strtolower($username));

            $email = strtolower(trim($item['email']));
            $user->setEmail($email);
            $user->setEmailCanonical($email);

            // ── Mot de passe hashé ────────────────────────────
            $hashedPassword = $this->passwordHasher->hashPassword($user, $item['plainPassword']);
            $user->setPassword($hashedPassword);
            
            // ── Rôles ─────────────────────────────────────────
            $user->setRoles($item['roles']);

            // ── Etat du compte ────────────────────────────────
            $user->setEnabled($item['enabled']);

            // ── Profil & pays ─────────────────────────────────
            $user->setProfile($item['profile'] ?? null);
            $user->setCountry(strtoupper($item['country']));

            // ── Téléphone ─────────────────────────────────────
            try {
                $phone = $this->phoneNumberUtil->parse($item['phone'], null);
                $user->setPhone($phone);
            } catch (\libphonenumber\NumberParseException $e) {
                echo sprintf("⚠️  Téléphone invalide pour %s : %s\n", $username, $e->getMessage());
                continue;
            }

            // ── Avatar depuis picsum.photos ───────────────────
            if (!empty($item['avatar_url'])) {
                $tmpFile    = $tmpDir . '/avatar_' . $index . '.jpg';
                $imgContent = @file_get_contents($item['avatar_url']);

                if ($imgContent !== false) {
                    file_put_contents($tmpFile, $imgContent);

                    $uploadedFile = new UploadedFile(
                        $tmpFile,
                        'avatar_' . $username . '.jpg',
                        'image/jpeg',
                        null,
                        true // test mode
                    );
                    $user->setAvatarFile($uploadedFile);
                } else {
                    echo sprintf("⚠️  Avatar non téléchargé pour : %s\n", $username);
                }
            }

            // ── Dates ─────────────────────────────────────────
            $user->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
            $this->canonicalFieldsUpdater->updateCanonicalFields($user);
            $manager->persist($user);

            // Référence partagée (utile si d'autres fixtures lient des users)
            $this->addReference(self::USER_REFERENCE_PREFIX . $index, $user);
            $loaded++;
        }

        $manager->flush();

        // Nettoyage des fichiers temporaires
        array_map('unlink', glob($tmpDir . '/avatar_*.jpg') ?: []);

        echo sprintf("✅ %d utilisateurs chargés.\n", $loaded);
    }

    public static function getGroups(): array
    {
        return ['users', 'security'];
    }
}
