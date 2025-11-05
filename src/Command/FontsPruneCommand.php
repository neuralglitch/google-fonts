<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Command;

use NeuralGlitch\GoogleFonts\Service\FontLockManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'gfonts:prune',
    description: 'Remove unused fonts from locked fonts'
)]
final class FontsPruneCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $fontsDir,
        private readonly string $manifestFile,
        private readonly FontLockManager $lockManager,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('template-dirs', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Template directories to scan')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be removed without actually removing')
            ->setHelp($this->getHelpText());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Prune Unused Fonts');

        $dryRun = $input->getOption('dry-run');

        // Get template directories
        $templateDirsArg = $input->getArgument('template-dirs');
        $templateDirs = is_array($templateDirsArg) ? $templateDirsArg : [];

        if (empty($templateDirs)) {
            $defaultDirs = [
                $this->projectDir . '/templates',
                $this->projectDir . '/views',
            ];
            $templateDirs = array_filter($defaultDirs, 'is_dir');

            if (empty($templateDirs)) {
                $io->error('No template directories found');

                return Command::FAILURE;
            }
        }

        // Scan templates for currently used fonts
        $io->section('Scanning templates');
        $usedFonts = $this->lockManager->scanTemplates($templateDirs);

        // Load current manifest
        if (!file_exists($this->manifestFile)) {
            $io->warning('No manifest file found. Nothing to prune.');

            return Command::SUCCESS;
        }

        $manifestContent = file_get_contents($this->manifestFile);
        if (false === $manifestContent) {
            $io->error('Failed to read manifest file');

            return Command::FAILURE;
        }

        $manifest = json_decode($manifestContent, true);
        if (!is_array($manifest) || !isset($manifest['fonts']) || !is_array($manifest['fonts'])) {
            $io->error('Invalid manifest file');

            return Command::FAILURE;
        }

        // Find unused fonts
        $lockedFonts = array_keys($manifest['fonts']);
        $usedFontNames = array_keys($usedFonts);
        $unusedFonts = array_diff($lockedFonts, $usedFontNames);

        if (empty($unusedFonts)) {
            $io->success('No unused fonts found. All locked fonts are in use!');

            return Command::SUCCESS;
        }

        // Show unused fonts
        $io->section('Unused Fonts');
        $io->listing(array_map(fn (string $font): string => "<info>{$font}</info>", $unusedFonts));

        if ($dryRun) {
            $io->note('Dry-run mode: no files will be removed');
            $io->info(sprintf('Would remove %d font(s)', count($unusedFonts)));

            return Command::SUCCESS;
        }

        // Confirm removal
        if (!$io->confirm(sprintf('Remove %d unused font(s)?', count($unusedFonts)), false)) {
            $io->info('Cancelled');

            return Command::SUCCESS;
        }

        // Remove unused fonts
        $removed = 0;
        foreach ($unusedFonts as $fontName) {
            $fontConfig = $manifest['fonts'][$fontName];

            // Remove font files
            if (isset($fontConfig['files']) && is_array($fontConfig['files'])) {
                foreach ($fontConfig['files'] as $file) {
                    $filePath = $this->fontsDir . '/' . $file;
                    if (file_exists($filePath)) {
                        $this->filesystem->remove($filePath);
                    }
                }
            }

            // Remove CSS file
            if (isset($fontConfig['css'])) {
                $cssPath = $this->projectDir . '/' . $fontConfig['css'];
                if (file_exists($cssPath)) {
                    $this->filesystem->remove($cssPath);
                }
            }

            // Remove from manifest
            unset($manifest['fonts'][$fontName]);
            ++$removed;

            $io->writeln(sprintf('<info>Removed: %s</info>', $fontName));
        }

        // Save updated manifest
        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (false === $json) {
            $io->error('Failed to encode manifest');

            return Command::FAILURE;
        }

        $this->filesystem->dumpFile($this->manifestFile, $json);

        $io->success(sprintf('Removed %d unused font(s)', $removed));
        $io->note(sprintf('Kept %d font(s) that are still in use', count($manifest['fonts'])));

        return Command::SUCCESS;
    }

    private function getHelpText(): string
    {
        $lines = [
            'The <info>%command.name%</info> command removes unused fonts from locked fonts.',
            '',
            'It scans your templates for google_fonts() calls and removes any locked fonts',
            'that are no longer referenced.',
            '',
            'Usage:',
            '  <info>php %command.full_name%</info>',
            '  <info>php %command.full_name% templates/ --dry-run</info>',
            '',
            'Options:',
            '  --dry-run    Show what would be removed without actually removing',
        ];

        return implode("\n", $lines);
    }
}
