<?php

declare(strict_types=1);

namespace DotTest\Log\Processor;

use Dot\Log\Processor\ReferenceId;
use PHPUnit\Framework\TestCase;

class ReferenceIdTest extends TestCase
{
    public function testKeepsAnExistingReferenceId(): void
    {
        $input = ['context' => ['referenceId' => 'something']];

        $this->assertSame($input, (new ReferenceId())->process($input));
    }

    public function testAddsTheReferenceId(): void
    {
        $processor = (new ReferenceId())->setReferenceId('ref-1');

        $this->assertSame('ref-1', $processor->getReferenceId());
        $this->assertSame(['context' => ['referenceId' => 'ref-1']], $processor->process([]));
    }

    public function testGeneratesAReferenceId(): void
    {
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', (new ReferenceId())->getReferenceId());
    }
}
