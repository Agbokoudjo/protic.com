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

use App\Entity\ManuscriptSubmission;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Symfony\Component\DependencyInjection\Attribute\WhenNot;

#[WhenNot('prod')]
class ManuscriptSubmissionFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly string $projectDir,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $jsonPath = __DIR__ . '/data/manuscript_submissions.json';

        if (!file_exists($jsonPath)) {
            throw new \RuntimeException(sprintf('Fichier introuvable : %s', $jsonPath));
        }

        $data = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        // Dossier temporaire pour les PDF téléchargés
        $tmpDir = $this->projectDir . '/var/fixtures_tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $loaded = 0;
         $phoneNumberUtil=PhoneNumberUtil::getInstance() ;
        foreach ($data as $index => $item) {
            $submission = new ManuscriptSubmission();

            $submission->setFullName($item['fullName']);
            $submission->setEmail(strtolower(trim($item['email'])));
            $submission->setSubject($item['subject']);
            $submission->setMessage($item['message']);
            $submission->setStatus($item['status']);
            $submission->setCountry(strtoupper($item['country']));
            $submission->setSubmittedAt(new \DateTimeImmutable($item['submittedAt']));

            // ── Téléphone ─────────────────────────────────────
            try {
                $phone = $phoneNumberUtil->parse($item['phone'], null);
                $submission->setPhone($phone);
            } catch (\libphonenumber\NumberParseException $e) {
                echo sprintf("⚠️  Téléphone invalide pour %s : %s\n", $item['fullName'], $e->getMessage());
                continue;
            }

            // ── PDF manuscrit depuis arxiv.org ─────────────────
            // arxiv fournit des PDFs publics et libres d'accès
            // On télécharge et on crée un UploadedFile pour VichUploader
            if (!empty($item['manuscript_pdf_url'])) {
                $tmpFile = $tmpDir . '/manuscript_' . $index . '.pdf';

                $context = stream_context_create([
                    'http' => [
                        'timeout'          => 15,
                        'follow_location'  => true,
                        'user_agent'       => 'ProTIC-Fixtures/1.0',
                    ],
                ]);

                $pdfContent = @file_get_contents($item['manuscript_pdf_url'], false, $context);

                if ($pdfContent !== false && strlen($pdfContent) > 1000) {
                    file_put_contents($tmpFile, $pdfContent);

                    $uploadedFile = new UploadedFile(
                        $tmpFile,
                        'manuscript_' . ($index + 1) . '.pdf',
                        'application/pdf',
                        null,
                        true // test mode
                    );

                    $submission->setManuscriptfile($uploadedFile);
                    echo sprintf("📄 PDF téléchargé pour : %s\n", $item['fullName']);
                } else {
                    // Fallback : on génère un nom de fichier fictif sans upload réel
                    // VichUploader ne sera pas déclenché mais le nom est enregistré
                    $submission->setManuscriptfilename(
                        sprintf('manuscript_%s_%d.pdf', strtolower(preg_replace('/\s+/', '_', $item['fullName'])), $index + 1)
                    );
                    echo sprintf("⚠️  PDF non disponible pour %s — nom fictif enregistré\n", $item['fullName']);
                }
            }

            $manager->persist($submission);
            $loaded++;
        }

        $manager->flush();

        // Nettoyage des fichiers temporaires
        array_map('unlink', glob($tmpDir . '/manuscript_*.pdf') ?: []);

        echo sprintf("✅ %d soumissions de manuscrits chargées.\n", $loaded);
    }

    public static function getGroups(): array
    {
        return ['catalogue', 'manuscript'];
    }
}
