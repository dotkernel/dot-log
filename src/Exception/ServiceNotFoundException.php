<?php

declare(strict_types=1);

namespace Dot\Log\Exception;

use Psr\Container\NotFoundExceptionInterface;

class ServiceNotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
}
