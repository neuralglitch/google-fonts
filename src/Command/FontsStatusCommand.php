<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'gfonts:status',
    description: 'Show Google Fonts configuration and status'
)]
final class FontsStatusCommand extends Command
{
    public function __construct(
        private readonly string $environment,
        private readonly bool $useLockedFonts,
        private readonly string $manifestFile,
        private readonly string $fontsDir,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp($this->getHelpText());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Google Fonts Status');

        // Environment
        $io->section('Environment');
        $io->table(
            ['Setting', 'Value'],
            [
                ['Environment', $this->environment],
                ['Use Locked Fonts', $this->useLockedFonts ? 'Yes' : 'No'],
            ]
        );

        // Paths
        $io->section('Paths');
        $io->table(
            ['Setting', 'Value', 'Exists'],
            [
                ['Fonts Directory', $this->fontsDir, file_exists($this->fontsDir) ? 'Yes' : 'No'],
                ['Manifest File', $this->manifestFile, file_exists($this->manifestFile) ? 'Yes' : 'No'],
            ]
        );

        // Locked fonts
        $io->section('Locked Fonts');
        if (file_exists($this->manifestFile)) {
            if (method_exists($this->filesystem, 'readFile')) {
                /** @phpstan-ignore-next-line - readFile() available in Symfony 7.1+ */
                $content = $this->filesystem->readFile($this->manifestFile);
            } else {
                $content = file_get_contents($this->manifestFile);
                if (false === $content) {
                    $io->error('Failed to read manifest file');

                    return Command::FAILURE;
                }
            }

            $manifest = json_decode($content, true);
            if (is_array($manifest) && isset($manifest['fonts']) && is_array($manifest['fonts'])) {
                $fonts = [];
                foreach ($manifest['fonts'] as $name => $config) {
                    $fonts[] = [
                        $name,
                        isset($config['monospace']) && $config['monospace'] ? 'Yes' : 'No',
                        implode(', ', $config['weights'] ?? []),
                        implode(', ', $config['styles'] ?? []),
                    ];
                }

                if (!empty($fonts)) {
                    $io->table(['Font', 'Monospace', 'Weights', 'Styles'], $fonts);
                    $io->success(sprintf('Found %d locked font(s)', count($fonts)));
                } else {
                    $io->warning('No fonts locked yet');
                }

                if (isset($manifest['generated_at'])) {
                    $io->info(sprintf('Last locked: %s', $manifest['generated_at']));
                }
            } else {
                $io->error('Invalid manifest file');

                return Command::FAILURE;
            }
        } else {
            $io->warning('No manifest file found. Run "php bin/console gfonts:lock" to generate it.');
        }

        // Locked Fonts Readiness
        $io->section('Locked Fonts Readiness');
        $checks = [
            ['Use locked fonts is enabled', $this->useLockedFonts],
            ['Manifest file exists', file_exists($this->manifestFile)],
            ['Fonts directory exists', file_exists($this->fontsDir)],
        ];

        $allPassed = true;
        foreach ($checks as [$check, $passed]) {
            if ($passed) {
                $io->success($check);
            } else {
                $io->error($check);
                $allPassed = false;
            }
        }

        if ($allPassed) {
            $io->success('Ready to use locked fonts!');
        } else {
            $io->warning('Not ready to use locked fonts. See checks above.');
            $io->note([
                'To enable locked fonts:',
                '  1. Run: php bin/console gfonts:lock',
                '  2. Configure: when@prod (or when@dev) in config/packages/google_fonts.yaml',
                '',
                'For troubleshooting, see DEBUG_LOCKED_FONTS.md',
            ]);
        }

        return Command::SUCCESS;
    }

    private function getHelpText(): string
    {
        $lines = [
            'The <info>%command.name%</info> command shows the current Google Fonts configuration and status.',
            '',
            'Usage:',
            '  <info>php %command.full_name%</info>',
            '',
            'This command displays:',
            '  - Current environment (dev/prod)',
            '  - Locked fonts configuration',
            '  - Manifest file status',
            '  - List of locked fonts',
            '  - Production readiness checks',
        ];

        return implode("\n", $lines);
    }
}
