<?php

declare(strict_types=1);

namespace DotTest\Log\Formatter;

use DateTime;
use Dot\Log\Formatter\FormatterInterface;
use Dot\Log\Formatter\Json;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    private Json $subject;

    protected function setUp(): void
    {
        $this->subject = new Json();
    }

    public function testFormat(): void
    {
        $input  = ['awesome array', 'timestamp' => new DateTime()];
        $result = $this->subject->format($input);
        $this->assertIsString($result);
    }

    public function testGetDatetimeFormat(): void
    {
        $result = $this->subject->getDateTimeFormat();
        $this->assertSame(FormatterInterface::DEFAULT_DATETIME_FORMAT, $result);
    }

    public function testSetDatetimeFormat(): void
    {
        $result = $this->subject->setDateTimeFormat('Y-m-d H:i:s');
        $this->assertContainsOnlyInstancesOf(Json::class, [$result]);
    }
}
