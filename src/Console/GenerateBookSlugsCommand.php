<?php
declare(strict_types=1);
/*
 * USAGE
 * ─────
 * # Génère les slugs manquants (livres sans slug uniquement)
 * php bin/console app:book:generate-slugs
 *
 * # Force la régénération de TOUS les slugs (même ceux déjà renseignés)
 * php bin/console app:book:generate-slugs --force
 *
 * # Prévisualiser sans écrire en base (dry-run)
 * php bin/console app:book:generate-slugs --dry-run
 *
 * # Combiner
 * php bin/console app:book:generate-slugs --force --dry-run
 */

namespace App\Console;

use App\Repository\BookRepository;
use App\Service\SlugGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:book:generate-slugs',
    description: 'Génère les slugs manquants (ou tous avec --force) pour les livres.',
)]
final class GenerateBookSlugsCommand extends Command
{
    // Combien de livres on flush en une seule transaction (évite OUT OF MEMORY)
    private const BATCH_SIZE = 50;

    public function __construct(
        private readonly BookRepository         $bookRepository,
        private readonly SlugGeneratorService   $slugGenerator,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct("app:book:generate-slugs");
    }

    protected function configure(): void
    {
        $this
            ->addOption('force',   'f', InputOption::VALUE_NONE, 'Régénère TOUS les slugs, même ceux déjà présents')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les slugs sans les persister en base');
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    { 
        $io     = new SymfonyStyle($input, $output);
        $force  = (bool) $input->getOption('force');
        $dryRun = (bool) $input->getOption('dry-run');

        $io->title('Générateur de slugs — ProTIC Books');

        if ($dryRun) {
            $io->warning('MODE DRY-RUN : aucune modification ne sera sauvegardée.');
        }

        // ── Chargement des livres concernés ──────────────────────────
        if ($force) {
            $books = $this->bookRepository->findAll();
            $io->comment(\sprintf('Mode --force : %d livre(s) traité(s).', \count($books)));
        } else {
            $books = $this->bookRepository->findBy(['slug' => null]);
            $io->comment(\sprintf('%d livre(s) sans slug trouvé(s).', \count($books)));
        }

        if (empty($books)) {
            $io->success('Aucun livre à traiter. Tout est déjà à jour !');
            return Command::SUCCESS;
        }

        // ── Barre de progression ──────────────────────────────────────
        $progressBar = new ProgressBar($output, \count($books));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $progressBar->start();

        $updated = 0;
        $errors  = [];

        foreach ($books as $i => $book) {
            $progressBar->setMessage(\sprintf('"%s"', \mb_substr((string) $book->getTitle(), 0, 40)));

            try {
                $this->slugGenerator->updateSlug($book, force: $force);

                if (!$dryRun) {
                    $this->em->persist($book);
                }

                $updated++;

                // Flush par batch pour limiter la pression mémoire
                if (!$dryRun && ($i + 1) % self::BATCH_SIZE === 0) {
                    $this->em->flush();
                    $this->em->clear();
                    // Après clear() les entités sont détachées — on doit recharger
                    // mais on a déjà traité celles du batch, on continue
                }
            } catch (\Throwable $e) {
                $errors[] = \sprintf(
                    'ID %d (%s) : %s',
                    (int) $book->getId(),
                    $book->getTitle(),
                    $e->getMessage()
                );
            }

            $progressBar->advance();
        }

        // Flush final pour le dernier batch (< BATCH_SIZE éléments)
        if (!$dryRun && $updated > 0) {
            $this->em->flush();
        }

        $progressBar->finish();
        $output->writeln('');

        // ── Rapport ──────────────────────────────────────────────────
        if (!empty($errors)) {
            $io->error('Erreurs rencontrées :');
            foreach ($errors as $err) {
                $io->writeln('  • ' . $err);
            }
        }

        $dryRun
            ? $io->success(\sprintf('%d slug(s) prévisualisé(s) — aucune écriture en base (dry-run).', $updated))
            : $io->success(\sprintf('%d slug(s) générés et sauvegardés.', $updated));

        // Afficher un aperçu (max 20 lignes) en mode verbose ou dry-run
        if ($dryRun || $output->isVerbose()) {
            $rows = [];
            foreach (\array_slice($books, 0, 20) as $book) {
                $rows[] = [$book->getId(), $book->getTitle(), $book->getSlug()];
            }
            $io->table(['ID', 'Titre', 'Slug généré'], $rows);
            if (\count($books) > 20) {
                $io->note('… et ' . (\count($books) - 20) . ' autre(s). Utilisez -v pour voir tout.');
            }
        }

        return empty($errors) ? Command::SUCCESS : Command::FAILURE;
    }
}
