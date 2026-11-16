<?php

declare(strict_types=1);

namespace DotTest\Log\Filter;

use ArrayObject;
use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Filter\SuppressFilter;
use PHPUnit\Framework\TestCase;

class SuppressFilterTest extends TestCase
{
    public function testAcceptsByDefault(): void
    {
        $this->assertTrue((new SuppressFilter())->filter([]));
    }

    public function testSuppress(): void
    {
        $filter = new SuppressFilter(['suppress' => true]);
        $this->assertFalse($filter->filter([]));

        $filter->suppress(false);
        $this->assertTrue($filter->filter([]));

        $this->assertFalse((new SuppressFilter(new ArrayObject(['suppress' => true])))->filter([]));
    }

    public function testWillNotInstantiate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Suppress must be a boolean; received "string"');

        new SuppressFilter(['suppress' => 'oups']);
    }
}
