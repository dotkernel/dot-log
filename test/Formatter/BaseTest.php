<?php

declare(strict_types=1);

namespace DotTest\Log\Formatter;

use Dot\Log\Formatter\Base;
use Dot\Log\Formatter\FormatterInterface;
use PHPUnit\Framework\TestCase;

class BaseTest extends TestCase
{
    private Base $subject;

    protected function setUp(): void
    {
        $this->subject = new Base();
    }

    public function testWillInstantiate(): void
    {
        $this->assertContainsOnlyInstancesOf(Base::class, [$this->subject]);
    }

    public function testFormat(): void
    {
        $input  = ['value'];
        $result = $this->subject->format($input);
        $this->assertSame($input, $result);
    }

    public function testGetDatetimeFormat(): void
    {
        $result = $this->subject->getDateTimeFormat();
        $this->assertSame(FormatterInterface::DEFAULT_DATETIME_FORMAT, $result);
    }

    public function testSetDatetimeFormat(): void
    {
        $result = $this->subject->setDateTimeFormat('Y-m-d H:i:s');
        $this->assertContainsOnlyInstancesOf(Base::class, [$this->subject]);
    }
}
