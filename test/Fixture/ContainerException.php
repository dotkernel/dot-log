<?php

declare(strict_types=1);

namespace DotTest\Log\Fixture;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

final class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}
