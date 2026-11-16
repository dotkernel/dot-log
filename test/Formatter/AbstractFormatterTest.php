<?php

declare(strict_types=1);

namespace DotTest\Log\Formatter;

use ArrayObject;
use DateTime;
use DateTimeImmutable;
use Dot\Log\Formatter\AbstractFormatter;
use Dot\Log\Formatter\FormatterInterface;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

use function fclose;
use function fopen;

class AbstractFormatterTest extends TestCase
{
    private AbstractFormatter $subject;

    protected function setUp(): void
    {
        $this->subject = new class extends AbstractFormatter {
            /**
             * @param array<string, mixed> $event
             */
            public function format(array $event): string
            {
                return '';
            }
        };
    }

    public function testNormalizeEvent(): void
    {
        $resource = fopen('php://memory', 'r');
        $this->assertNotFalse($resource);

        $normalized = $this->subject->normalizeEvent([
            'scalar'     => 1,
            'null'       => null,
            'array'      => ['a' => 'b/c'],
            'iterable'   => new ArrayObject(['x' => 1]),
            'date'       => new DateTimeImmutable('2026-01-02 03:04:05+00:00'),
            'mutable'    => new DateTime('2026-01-02 03:04:05+00:00'),
            'stringable' => new class implements Stringable {
                public function __toString(): string
                {
                    return 'text';
                }
            },
            'object'     => new stdClass(),
            'resource'   => $resource,
            'extra'      => ['nested' => ['k' => 'v']],
        ]);
        fclose($resource);

        $this->assertSame([
            'scalar'     => 1,
            'null'       => null,
            'array'      => '{"a":"b/c"}',
            'iterable'   => '{"x":1}',
            'date'       => '2026-01-02T03:04:05+00:00',
            'mutable'    => '2026-01-02T03:04:05+00:00',
            'stringable' => 'text',
            'object'     => 'object(stdClass) {}',
            'resource'   => 'resource(stream)',
            'extra'      => ['nested' => '{"k":"v"}'],
        ], $normalized);
    }

    public function testDateTimeFormat(): void
    {
        $this->assertSame(FormatterInterface::DEFAULT_DATETIME_FORMAT, $this->subject->getDateTimeFormat());
        $this->assertSame($this->subject, $this->subject->setDateTimeFormat('Y-m-d'));
        $this->assertSame(
            ['date' => '2026-01-02'],
            $this->subject->normalizeEvent(['date' => new DateTimeImmutable('2026-01-02')])
        );
    }
}
