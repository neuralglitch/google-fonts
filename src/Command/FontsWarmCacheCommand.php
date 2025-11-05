<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Command;

use NeuralGlitch\GoogleFonts\Service\FontDownloader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'gfonts:warm-cache',
    description: 'Pre-warm font cache from manifest file'
)]
final class FontsWarmCacheCommand extends Command
{
    public function __construct(
        private readonly FontDownloader $fontDownloader,
        private readonly string $manifestFile,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('manifest', 'm', InputOption::VALUE_REQUIRED, 'Path to manifest file', null)
            ->setHelp($this->getHelpText());
    }

    private function getHelpText(): string
    {
        $lines = [
            'The <info>%command.name%</info> command pre-downloads all fonts listed in the manifest file.',
            'This is useful for CI/CD builds to ensure all fonts are available before deployment.',
            '',
            'Example:',
            '  <info>php %command.full_name%</info>',
        ];

        return implode("\n", $lines);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Warm Font Cache');

        $manifestOption = $input->getOption('manifest');
        $manifestPath = is_string($manifestOption) ? $manifestOption : $this->manifestFile;

        if (!$this->filesystem->exists($manifestPath)) {
            $io->error(sprintf('Manifest file not found: %s', $manifestPath));
            $io->note('Run "gfonts:lock" first to create a manifest file.');

            return Command::FAILURE;
        }

        // Use Filesystem::readFile() if available (Symfony 7.1+), otherwise file_get_contents()
        if (method_exists($this->filesystem, 'readFile')) {
            /** @phpstan-ignore-next-line - readFile() available in Symfony 7.1+ */
            $content = $this->filesystem->readFile($manifestPath);
        } else {
            $content = file_get_contents($manifestPath);
            if (false === $content) {
                $io->error('Failed to read manifest file.');

                return Command::FAILURE;
            }
        }

        $manifest = json_decode($content, true);

        if (!is_array($manifest) || !isset($manifest['fonts']) || !is_array($manifest['fonts'])) {
            $io->error('Invalid manifest file format.');

            return Command::FAILURE;
        }

        $fonts = $manifest['fonts'];
        $io->section(sprintf('Found %d font(s) in manifest', count($fonts)));

        $progressBar = $io->createProgressBar(count($fonts));
        $progressBar->start();

        $success = 0;
        $failed = 0;

        foreach ($fonts as $fontName => $config) {
            if (!is_string($fontName) || !is_array($config)) {
                continue;
            }

            try {
                $weights = is_array($config['weights'] ?? null) ? $config['weights'] : [400];
                $styles = is_array($config['styles'] ?? null) ? array_map('strval', $config['styles']) : ['normal'];
                $display = is_string($config['display'] ?? null) ? $config['display'] : 'swap';

                $this->fontDownloader->downloadFont($fontName, $weights, $styles, $display);
                ++$success;
            } catch (\Exception $e) {
                ++$failed;
                $io->newLine(2);
                $io->warning(sprintf('Failed to download font "%s": %s', $fontName, $e->getMessage()));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        if (0 === $failed) {
            $io->success(sprintf('Successfully warmed cache for %d font(s)', $success));

            return Command::SUCCESS;
        }

        $io->warning(
            sprintf(
                'Cache warmed with %d success(es) and %d failure(s)',
                $success,
                $failed
            )
        );

        return Command::FAILURE;
    }
}
