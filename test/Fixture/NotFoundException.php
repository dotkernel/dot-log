<?php

declare(strict_types=1);

namespace DotTest\Log\Fixture;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

final class NotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
