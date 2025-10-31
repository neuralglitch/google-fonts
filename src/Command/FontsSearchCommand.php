<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Command;

use NeuralGlitch\GoogleFonts\Service\GoogleFontsApi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'gfonts:search',
    description: 'Search Google Fonts catalog'
)]
final class FontsSearchCommand extends Command
{
    public function __construct(
        private readonly GoogleFontsApi $api
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('query', InputArgument::OPTIONAL, 'Search query')
            ->addOption('max-results', 'm', InputOption::VALUE_REQUIRED, 'Maximum number of results', '20')
            ->setHelp($this->getHelpText());
    }

    private function getHelpText(): string
    {
        $lines = [
            'The <info>%command.name%</info> command searches the Google Fonts catalog.',
            '',
            'Example:',
            '  <info>php %command.full_name% Roboto</info>',
            '  <info>php %command.full_name% "Open Sans"</info>',
        ];

        return implode("\n", $lines);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $queryArg = $input->getArgument('query');
        $query = is_string($queryArg) ? $queryArg : '';
        $maxResultsOption = $input->getOption('max-results');
        $maxResults = is_string($maxResultsOption) ? (int) $maxResultsOption : 20;

        $io->title('Search Google Fonts');

        if ('' === $query) {
            $io->section('Popular Fonts');
        } else {
            $io->section(sprintf('Search Results for: "%s"', $query));
        }

        try {
            $fonts = $this->api->searchFonts($query, $maxResults);

            if (!is_array($fonts) || empty($fonts)) {
                $io->warning('No fonts found.');

                return Command::SUCCESS;
            }

            $rows = [];
            foreach ($fonts as $font) {
                if (!is_array($font)) {
                    continue;
                }
                $variants = is_array($font['variants'] ?? null) ? $font['variants'] : [];
                $rows[] = [
                    is_string($font['family'] ?? null) ? $font['family'] : 'N/A',
                    count($variants),
                    implode(', ', array_slice($variants, 0, 5)) . (count($variants) > 5 ? '...' : ''),
                    is_string($font['category'] ?? null) ? $font['category'] : 'N/A',
                ];
            }

            $io->table(
                ['Font Name', 'Variants', 'Available Variants', 'Category'],
                $rows
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error([
                'Failed to search fonts',
                $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
