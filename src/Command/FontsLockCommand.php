<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Command;

use NeuralGlitch\GoogleFonts\Service\FontLockManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'gfonts:lock',
    description: 'Scan templates and lock all used fonts for production'
)]
final class FontsLockCommand extends Command
{
    public function __construct(
        private readonly FontLockManager $lockManager,
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'template-dirs',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Template directories to scan',
                []
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force re-download even if fonts already exist')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be downloaded without actually downloading')
            ->setHelp($this->getHelpText());
    }

    private function getHelpText(): string
    {
        $lines = [
            'The <info>%command.name%</info> command scans Twig templates for google_fonts() function calls,',
            'downloads all referenced fonts, and creates a manifest file for production use.',
            '',
            'Example:',
            '  <info>php %command.full_name%</info>',
            '  <info>php %command.full_name% templates/ views/</info>',
        ];

        return implode("\n", $lines);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Lock Fonts');

        $templateDirsArg = $input->getArgument('template-dirs');
        $templateDirs = is_array($templateDirsArg) ? $templateDirsArg : [];

        // Default to common template directories
        if (empty($templateDirs)) {
            $defaultDirs = [
                $this->projectDir . '/templates',
                $this->projectDir . '/views',
            ];
            $templateDirs = array_filter($defaultDirs, 'is_dir');

            if (empty($templateDirs)) {
                $io->error('No template directories found. Please specify template directories as arguments.');

                return Command::FAILURE;
            }
        }

        // Ensure all directories are strings
        $templateDirs = array_filter($templateDirs, 'is_string');

        $io->section('Scanning templates');
        $io->listing(array_map(fn (string $dir): string => "<info>{$dir}</info>", $templateDirs));

        $fonts = $this->lockManager->scanTemplates($templateDirs);

        if (empty($fonts)) {
            $io->warning('No google_fonts() function calls found in templates.');

            return Command::SUCCESS;
        }

        $io->section('Found fonts');
        $fontList = [];
        foreach ($fonts as $name => $config) {
            /** @var array{weights?: array<int|string>, styles?: array<string>} $config */
            $weightsValue = $config['weights'] ?? null;
            $weights = is_array($weightsValue) ? $weightsValue : [];
            $stylesValue = $config['styles'] ?? null;
            $styles = is_array($stylesValue) ? $stylesValue : [];
            $fontList[] = sprintf(
                '<info>%s</info> (weights: %s, styles: %s)',
                $name,
                implode(', ', array_map('strval', $weights)),
                implode(', ', array_map('strval', $styles))
            );
        }
        $io->listing($fontList);

        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Dry-run mode: no fonts will be downloaded');
            $io->info(sprintf('Would download %d font(s)', count($fonts)));

            return Command::SUCCESS;
        }

        $io->section('Downloading fonts');

        // Create progress bar
        $progressBar = $io->createProgressBar(count($fonts));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $progressBar->setMessage('Starting...');

        try {
            $manifest = $this->lockManager->lockFonts($fonts, function ($current, $total, $fontName) use ($progressBar) {
                $progressBar->setMessage(sprintf('Downloading %s', $fontName));
                $progressBar->advance();
            });

            $progressBar->finish();
            $io->newLine(2);

            if (!isset($manifest['fonts']) || !is_array($manifest['fonts'])) {
                throw new \RuntimeException('Invalid manifest structure returned from lockFonts');
            }

            $io->success([
                sprintf('Locked %d font(s) successfully!', count($manifest['fonts'])),
            ]);

            // Show locked fonts with detailed information
            $fontList = [];
            $totalMissing = 0;

            foreach ($manifest['fonts'] as $name => $config) {
                if (!is_array($config)) {
                    continue;
                }

                // Get requested weights from scan (what was in templates)
                $requestedFontConfig = $fonts[$name] ?? [];
                $requestedWeights = isset($requestedFontConfig['weights'])
                    ? array_map('intval', $requestedFontConfig['weights'])
                    : [];

                // Get downloaded weights (what was actually available from Google)
                $downloadedWeights = isset($config['weights'])
                    ? array_map('intval', $config['weights'])
                    : [];

                // Check if all requested weights were downloaded
                $missingWeights = array_diff($requestedWeights, $downloadedWeights);
                $weightsDisplay = implode(', ', $downloadedWeights);

                if (!empty($missingWeights)) {
                    $weightsDisplay .= ' <fg=yellow>(missing: ' . implode(', ', $missingWeights) . ')</>';
                    $totalMissing += count($missingWeights);
                }

                $fontList[] = [
                    $name,
                    $weightsDisplay,
                    implode(', ', $config['styles'] ?? []),
                    count($config['files'] ?? []),
                ];
            }

            $io->table(['Font', 'Weights', 'Styles', 'Files'], $fontList);

            // Show warning if any weights were missing
            if ($totalMissing > 0) {
                $io->warning(sprintf(
                    '%d weight(s) were not available from Google Fonts and were skipped.',
                    $totalMissing
                ));
                $io->note('The manifest only includes weights that were actually downloaded.');
            }

            $io->note('Enable locked fonts in production by setting: google_fonts.use_locked_fonts: true');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error([
                'Failed to lock fonts',
                $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
