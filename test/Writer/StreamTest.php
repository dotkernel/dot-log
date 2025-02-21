<?php

declare(strict_types=1);

namespace DotTest\Log\Writer;

use Dot\Log\Exception\InvalidArgumentException;
use Dot\Log\Formatter\Json;
use Dot\Log\Logger;
use Dot\Log\Writer\Stream;
use ErrorException;
use PHPUnit\Framework\TestCase;
use stdClass;

use function fopen;

class StreamTest extends TestCase
{
    private Stream $subject;

    private array $options = [
        'stream'    => __DIR__ . '/../../log/error-log-{Y}-{m}-{d}.log',
        'filters'   => [
            'allMessages' => [
                'name'    => 'level',
                'options' => [
                    'operator' => '>=',
                    'level'    => Logger::EMERG,
                ],
            ],
        ],
        'formatter' => [
            'name' => Json::class,
        ],
    ];

    /**
     * @throws ErrorException
     */
    protected function setUp(): void
    {
        $this->subject = new Stream($this->options);
    }

    /**
     * @throws ErrorException
     */
    public function testWillNotInstantiateWithInt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource is not a stream nor a string; received "integer"');
        new Stream(47);
    }

    /**
     * @throws ErrorException
     */
    public function testWillNotInstantiateWithWrongResource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource is not a stream nor a string; received "object"');
        new Stream(['stream' => new stdClass()]);
    }

    /**
     * @throws ErrorException
     */
    public function testWillNotInstantiateWithWrongMode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mode must be "a" on existing streams; received "r"');
        new Stream(
            [
                'stream' => fopen(__DIR__ . '/../../log/error-log-{Y}-{m}-{d}.log', 'r'),
                'mode'   => 'r',
            ]
        );
    }

    public function testSetLogSeparator(): void
    {
        $input = "separator";

        $result = $this->subject->setLogSeparator($input);
        $this->assertSame($this->subject, $result);
    }

    public function testGetLogSeparator(): void
    {
        $this->assertIsString($this->subject->getLogSeparator());
    }

    public function testShutDown(): void
    {
        $this->expectNotToPerformAssertions();
        $this->subject->shutdown();
    }
}
