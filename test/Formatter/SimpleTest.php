<?php

declare(strict_types=1);

namespace DotTest\Log\Formatter;

use ArrayObject;
use DateTime;
use DateTimeImmutable;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Formatter\Simple;
use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function event(array $extra = []): array
    {
        return [
            'timestamp' => new DateTimeImmutable('2026-01-02 03:04:05+00:00'),
            'levelName' => 'critical',
            'level'     => 2,
            'message'   => 'Test Message',
            'extra'     => $extra,
        ];
    }

    public function testDefaultFormat(): void
    {
        $this->assertSame(
            '2026-01-02T03:04:05+00:00 critical (2): Test Message',
            (new Simple())->format($this->event())
        );
    }

    public function testExtraIsPrintedAsJson(): void
    {
        $this->assertSame(
            '2026-01-02T03:04:05+00:00 critical (2): Test Message {"user":"x"}',
            (new Simple())->format($this->event(['user' => 'x']))
        );
    }

    /**
     * Regression test: events from Logger have no "extra" key, and a literal "%extra%" was written.
     */
    public function testEventWithoutExtra(): void
    {
        $event = $this->event();
        unset($event['extra']);

        $this->assertSame('2026-01-02T03:04:05+00:00 critical (2): Test Message', (new Simple())->format($event));
    }

    public function testCustomFormatAndDateFormatFromOptions(): void
    {
        $formatter = new Simple(new ArrayObject(['format' => '%timestamp% %message%', 'dateTimeFormat' => 'Y']));

        $this->assertSame('2026 Test Message', $formatter->format($this->event()));
        $this->assertSame('Test Message', (new Simple('%message%'))->format($this->event()));
        $this->assertSame('2026', (new Simple('%timestamp%', 'Y'))->format($this->event()));
    }

    public function testWillNotInstantiate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format must be a string');

        new Simple(['format' => new DateTime()]);
    }
}
