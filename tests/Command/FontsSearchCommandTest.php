<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\Command;

use NeuralGlitch\GoogleFonts\Command\FontsSearchCommand;
use NeuralGlitch\GoogleFonts\Service\GoogleFontsApi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FontsSearchCommandTest extends TestCase
{
    public function testConfigureSetArgumentsAndOptions(): void
    {
        $api = new GoogleFontsApi(new MockHttpClient());
        $command = new FontsSearchCommand($api);

        $definition = $command->getDefinition();

        self::assertTrue($definition->hasArgument('query'));
        self::assertTrue($definition->hasOption('max-results'));

        // Test help text is set
        $help = $command->getHelp();
        self::assertNotEmpty($help);
        self::assertStringContainsString('searches the Google Fonts catalog', $help);
    }

    public function testExecuteSearchesForFonts(): void
    {
        $jsonData = json_encode([
            'items' => [
                ['family' => 'Roboto', 'variants' => ['regular', '700'], 'category' => 'sans-serif'],
            ],
        ]);
        $mockResponse = new MockResponse(false !== $jsonData ? $jsonData : '{}');

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient);

        $command = new FontsSearchCommand($api);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['query' => 'Roboto']);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Roboto', $output);
        self::assertStringContainsString('sans-serif', $output);
    }

    public function testExecuteHandlesEmptyQuery(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Ubuntu', 'variants' => ['regular'], 'category' => 'sans-serif'],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient);

        $command = new FontsSearchCommand($api);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Popular Fonts', $output);
    }

    public function testExecuteRespectsMaxResultsOption(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Ubuntu', 'variants' => ['regular'], 'category' => 'sans-serif'],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient);

        $command = new FontsSearchCommand($api);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['query' => 'Ubuntu', '--max-results' => '5']);

        self::assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecuteHandlesNoResults(): void
    {
        $mockResponse = new MockResponse((string) json_encode(['items' => []]));
        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient);

        $command = new FontsSearchCommand($api);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['query' => 'NonExistent']);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('No fonts found', $output);
    }

    public function testExecuteHandlesApiException(): void
    {
        $mockResponse = new MockResponse('', ['http_code' => 500]);
        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient);

        $command = new FontsSearchCommand($api);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['query' => 'Test']);

        self::assertSame(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Failed to search fonts', $output);
    }
}
