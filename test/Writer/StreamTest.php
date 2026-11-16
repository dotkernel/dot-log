<?php

declare(strict_types=1);

namespace DotTest\Log\Writer;

use DateTimeImmutable;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Exception\RuntimeException;
use Dot\Log\Formatter\Json;
use Dot\Log\Logger;
use Dot\Log\Writer\Stream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_map;
use function decoct;
use function fclose;
use function file_get_contents;
use function fileperms;
use function fopen;
use function glob;
use function is_dir;
use function mkdir;
use function rewind;
use function rmdir;
use function stream_get_contents;
use function sys_get_temp_dir;
use function time;
use function touch;
use function uniqid;
use function unlink;

use const PHP_EOL;

class StreamTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/dot-log-stream-' . uniqid();
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->directory . '/*') ?: []);
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function event(string $message = 'hello'): array
    {
        return [
            'timestamp' => new DateTimeImmutable('2026-01-02 03:04:05+00:00'),
            'level'     => Logger::INFO,
            'levelName' => 'info',
            'message'   => $message,
            'context'   => [],
        ];
    }

    /**
     * Regression test: a Stream without a formatter crashed on the first write.
     */
    public function testWritesWithTheDefaultFormatter(): void
    {
        $file   = $this->directory . '/default.log';
        $writer = new Stream($file);

        $writer->write($this->event());
        $writer->shutdown();

        $this->assertSame(
            '2026-01-02T03:04:05+00:00 info (6): hello' . PHP_EOL,
            file_get_contents($file)
        );
    }

    public function testWritesToAnExistingResource(): void
    {
        $resource = fopen('php://memory', 'a+');
        $this->assertNotFalse($resource);
        $writer = new Stream(['stream' => $resource, 'formatter' => ['name' => 'json'], 'log_separator' => "\n"]);

        $writer->write($this->event());

        rewind($resource);
        $this->assertSame(
            '{"timestamp":"2026-01-02T03:04:05+00:00","level":6,"levelName":"info","message":"hello","context":[]}'
            . "\n",
            stream_get_contents($resource)
        );
        fclose($resource);
    }

    public function testFiltersAreApplied(): void
    {
        $file   = $this->directory . '/filtered.log';
        $writer = new Stream([
            'stream'  => $file,
            'filters' => [['name' => 'level', 'options' => ['level' => Logger::ERR]]],
        ]);

        $writer->write($this->event());

        $this->assertSame('', file_get_contents($file));
    }

    public function testCreatesTheFileWithTheConfiguredPermissions(): void
    {
        $file = $this->directory . '/perm.log';
        new Stream(['stream' => $file, 'chmod' => 0640]);

        $this->assertSame('640', decoct(fileperms($file) & 0777));
    }

    public function testWritingToAClosedStreamFails(): void
    {
        $writer = new Stream($this->directory . '/closed.log');
        $writer->shutdown();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The log stream is closed');

        $writer->write($this->event());
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function provideInvalidStreams(): iterable
    {
        yield 'integer' => [47, 'integer'];
        yield 'object option' => [['stream' => new stdClass()], 'object'];
        yield 'missing option' => [[], 'NULL'];
    }

    #[DataProvider('provideInvalidStreams')]
    public function testWillNotInstantiateWithAnInvalidStream(mixed $stream, string $type): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource is not a stream nor a string; received "' . $type . '"');

        new Stream($stream);
    }

    public function testWillNotInstantiateWithWrongMode(): void
    {
        $resource = fopen('php://memory', 'r');
        $this->assertNotFalse($resource);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mode must be "a" on existing streams; received "r"');

        new Stream(['stream' => $resource, 'mode' => 'r']);
    }

    public function testWillNotOpenAMissingDirectory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be opened with mode "a"');

        new Stream($this->directory . '/missing/dir/file.log');
    }

    public function testLogSeparator(): void
    {
        $writer = new Stream('php://memory');

        $this->assertSame(PHP_EOL, $writer->getLogSeparator());
        $this->assertSame($writer, $writer->setLogSeparator('separator'));
        $this->assertSame('separator', $writer->getLogSeparator());
    }

    public function testFormatterGivenAsClassName(): void
    {
        $resource = fopen('php://memory', 'a+');
        $this->assertNotFalse($resource);
        $writer = new Stream(['stream' => $resource, 'formatter' => ['name' => Json::class]]);

        $writer->write(['message' => 'json']);

        rewind($resource);
        $this->assertSame('{"message":"json"}' . PHP_EOL, stream_get_contents($resource));
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function provideOldLogsPatterns(): iterable
    {
        yield 'no placeholder' => ['app.log', null];
        yield 'daily' => ['app-{Y}-{m}-{d}.log', '/^app\-[A-Za-z0-9]+\-[A-Za-z0-9]+\-[A-Za-z0-9]+\.log$/'];
        yield 'special characters' => ['app+{Y}.log', '/^app\+[A-Za-z0-9]+\.log$/'];
    }

    #[DataProvider('provideOldLogsPatterns')]
    public function testOldLogsPattern(string $streamFormat, ?string $expected): void
    {
        $writer = new Stream([
            'stream'        => $this->directory . '/current.log',
            'stream_format' => $streamFormat,
            'log_lifetime'  => 1,
        ]);

        $this->assertSame($expected, $writer->getOldLogsPattern());
    }

    /**
     * Regression test: only "." was escaped, so unrelated files could match the deletion pattern.
     */
    public function testShutdownDeletesOnlyOldFilesOfThisWriter(): void
    {
        $old         = time() - 5 * 86400;
        $oldLog      = $this->directory . '/app+2020-01.log';
        $recentLog   = $this->directory . '/app+2026-11.log';
        $unrelated   = $this->directory . '/appX2020-01Xlog';
        $otherWriter = $this->directory . '/other-2020-01.log';
        foreach ([$oldLog, $unrelated, $otherWriter] as $file) {
            touch($file, $old);
        }
        touch($recentLog);

        $writer = new Stream([
            'stream'        => $recentLog,
            'stream_format' => 'app+{Y}-{m}.log',
            'log_lifetime'  => 2,
        ]);
        $writer->shutdown();

        $this->assertFileDoesNotExist($oldLog);
        $this->assertFileExists($recentLog);
        $this->assertFileExists($unrelated);
        $this->assertFileExists($otherWriter);
    }

    public function testNoCleanupWithoutLifetime(): void
    {
        $oldLog = $this->directory . '/app-2020.log';
        touch($oldLog, time() - 5 * 86400);

        (new Stream(['stream' => $this->directory . '/app-2026.log', 'stream_format' => 'app-{Y}.log']))->shutdown();

        $this->assertFileExists($oldLog);
    }
}
