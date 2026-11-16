<?php

declare(strict_types=1);

namespace DotTest\Log\Formatter;

use DateTime;
use DateTimeImmutable;
use Dot\Log\Formatter\FormatterInterface;
use Dot\Log\Formatter\Json;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testFormat(): void
    {
        $result = (new Json())->format([
            'message'   => 'a/b ü',
            'timestamp' => new DateTime('2026-01-02 03:04:05+00:00'),
            'amount'    => 1.0,
        ]);

        $this->assertSame('{"message":"a/b ü","timestamp":"2026-01-02T03:04:05+00:00","amount":1.0}', $result);
    }

    /**
     * Regression test: DateTimeImmutable was serialized as an object.
     */
    public function testFormatsImmutableAndNestedDates(): void
    {
        $date   = new DateTimeImmutable('2026-01-02 03:04:05+00:00');
        $result = (new Json())->setDateTimeFormat('Y-m-d')->format([
            'timestamp' => $date,
            'context'   => ['since' => $date],
        ]);

        $this->assertSame('{"timestamp":"2026-01-02","context":{"since":"2026-01-02"}}', $result);
    }

    /**
     * Regression test: JSON_NUMERIC_CHECK turned "0712345678" into 712345678.
     */
    public function testKeepsNumericStrings(): void
    {
        $result = (new Json())->format(['context' => ['phone' => '0712345678', 'zip' => '01234']]);

        $this->assertSame('{"context":{"phone":"0712345678","zip":"01234"}}', $result);
    }

    public function testInvalidUtf8IsSubstituted(): void
    {
        $this->assertSame("{\"message\":\"a\u{FFFD}\"}", (new Json())->format(['message' => "a\xB1"]));
    }

    public function testDateTimeFormat(): void
    {
        $subject = new Json();

        $this->assertSame(FormatterInterface::DEFAULT_DATETIME_FORMAT, $subject->getDateTimeFormat());
        $this->assertSame($subject, $subject->setDateTimeFormat('Y-m-d H:i:s'));
        $this->assertSame('Y-m-d H:i:s', $subject->getDateTimeFormat());
    }
}
