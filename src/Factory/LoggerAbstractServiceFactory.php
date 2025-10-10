<?php

declare(strict_types=1);

namespace Dot\Log\Factory;

use Dot\Log\Logger;
use Dot\Log\LoggerServiceFactory;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function count;
use function date;
use function explode;
use function is_array;
use function pathinfo;
use function preg_match_all;
use function str_replace;

class LoggerAbstractServiceFactory extends LoggerServiceFactory implements AbstractFactoryInterface
{
    protected array $config;

    protected string $configKey;
    protected const PREFIX = 'dot-log';

    protected string $subConfigKey = 'loggers';

    public function __construct(string $configKey = 'dot_log')
    {
        $this->configKey = $configKey;
    }

    /**
     * @param string $requestedName
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function canCreate(ContainerInterface $container, $requestedName): bool
    {
        $parts = explode('.', $requestedName);
        if (count($parts) !== 2) {
            return false;
        }
        if ($parts[0] !== static::PREFIX) {
            return false;
        }

        $config = $this->getConfig($container);
        if (empty($config)) {
            return false;
        }

        return isset($config[$parts[1]]);
    }

    /**
     * @param string $requestedName
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Logger
    {
        $parts = explode('.', $requestedName);

        $config = $this->getConfig($container);
        $config = $config[$parts[1]];

        $this->processConfig($config, $container);

        return new Logger($config);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getConfig(ContainerInterface $services): array
    {
        if (! $services->has('config')) {
            $this->config = [];
        }

        $config = $services->get('config');
        if (isset($config[$this->configKey])) {
            $this->config = $config[$this->configKey];
        }

        if (
            ! empty($this->config)
            && isset($this->config[$this->subConfigKey])
            && is_array($this->config[$this->subConfigKey])
        ) {
            $this->config = $this->config[$this->subConfigKey];
        }

        return $this->config;
    }

    protected function processConfig(array &$config, ContainerInterface $services): void
    {
        if (isset($config['writers'])) {
            foreach ($config['writers'] as $index => $writerConfig) {
                if (! empty($writerConfig['options']['stream'])) {
                    $config['writers'][$index]['options']['stream_format'] =
                        pathinfo($writerConfig['options']['stream'])['basename'];
                    $config['writers'][$index]['options']['stream']        = self::parseVariables(
                        $writerConfig['options']['stream']
                    );
                }
            }
        }

        parent::processConfig($config, $services);
    }

    private static function parseVariables(string $stream): string
    {
        preg_match_all('/{([a-z])}/i', $stream, $matches);
        if (! empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $stream = str_replace('{' . $match . '}', date($match), $stream);
            }
        }

        return $stream;
    }
}
