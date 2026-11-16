<?php

declare(strict_types=1);

namespace DotTest\Log;

use Dot\Log\ConfigProvider;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    public function testRegistersNothing(): void
    {
        $provider = new ConfigProvider();

        $this->assertSame([], $provider());
        $this->assertSame([], $provider->getDependencies());
    }
}
