<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Command;

use NeuralGlitch\GoogleFonts\Service\FontDownloader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'gfonts:import',
    description: 'Import and download a Google Font for local use'
)]
final class FontsImportCommand extends Command
{
    public function __construct(
        private readonly FontDownloader $fontDownloader
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Font name (e.g., "Ubuntu", "Roboto")')
            ->addOption(
                'weights',
                'w',
                InputOption::VALUE_REQUIRED,
                'Font weights (comma-separated or space-separated)',
                '400'
            )
            ->addOption(
                'styles',
                's',
                InputOption::VALUE_REQUIRED,
                'Font styles (comma-separated or space-separated)',
                'normal'
            )
            ->addOption('display', 'd', InputOption::VALUE_REQUIRED, 'Font display value', 'swap')
            ->setHelp($this->getHelpText());
    }

    private function getHelpText(): string
    {
        $lines = [
            'The <info>%command.name%</info> command downloads a Google Font and stores it locally.',
            '',
            'Example:',
            '  <info>php %command.full_name% Ubuntu --weights="300,400,500,700" --styles="normal,italic"</info>',
        ];

        return implode("\n", $lines);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $fontName = $input->getArgument('name');
        $weightsInput = $input->getOption('weights');
        $stylesInput = $input->getOption('styles');
        $display = $input->getOption('display');

        if (!is_string($fontName)) {
            throw new \InvalidArgumentException('Font name must be a string');
        }
        if (!is_string($weightsInput)) {
            throw new \InvalidArgumentException('Weights must be a string');
        }
        if (!is_string($stylesInput)) {
            throw new \InvalidArgumentException('Styles must be a string');
        }
        if (!is_string($display)) {
            throw new \InvalidArgumentException('Display must be a string');
        }

        // Parse weights
        $weights = str_contains($weightsInput, ',')
            ? array_map('trim', explode(',', $weightsInput))
            : array_map('trim', explode(' ', $weightsInput));

        // Parse styles
        $styles = str_contains($stylesInput, ',')
            ? array_map('trim', explode(',', $stylesInput))
            : array_map('trim', explode(' ', $stylesInput));

        $io->title(sprintf('Importing font: %s', $fontName));
        $io->section('Configuration');
        $io->listing([
            sprintf('Font: <info>%s</info>', $fontName),
            sprintf('Weights: <info>%s</info>', implode(', ', $weights)),
            sprintf('Styles: <info>%s</info>', implode(', ', $styles)),
            sprintf('Display: <info>%s</info>', $display),
        ]);

        try {
            $result = $this->fontDownloader->downloadFont($fontName, $weights, $styles, $display);

            $io->success([
                sprintf('Font "%s" imported successfully!', $fontName),
                sprintf('Files saved: %d', count($result['files'])),
                sprintf('CSS file: %s', $result['cssPath']),
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error([
                sprintf('Failed to import font "%s"', $fontName),
                $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
