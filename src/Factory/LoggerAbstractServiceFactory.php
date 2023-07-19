<?php

declare(strict_types=1);

namespace Dot\Log\Factory;

use Dot\Mail\Service\MailServiceInterface;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Mail;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function count;
use function date;
use function explode;
use function in_array;
use function is_array;
use function is_string;
use function preg_match_all;
use function str_replace;

class LoggerAbstractServiceFactory extends \Laminas\Log\LoggerAbstractServiceFactory
{
    protected const PREFIX = 'dot-log';

    /** @var string $configKey */
    protected $configKey = 'dot_log';

    protected string $subConfigKey = 'loggers';

    /**
     * @param string $requestedName
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

        return parent::canCreate($container, $parts[1]);
    }

    /**
     * @param string $requestedName
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Logger
    {
        $parts = explode('.', $requestedName);
        return parent::__invoke($container, $parts[1], $options);
    }

    protected function getConfig(ContainerInterface $services): array
    {
        parent::getConfig($services);

        if (
            ! empty($this->config)
            && isset($this->config[$this->subConfigKey])
            && is_array($this->config[$this->subConfigKey])
        ) {
            $this->config = $this->config[$this->subConfigKey];
        }

        return $this->config;
    }

    /**
     * @param array $config
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function processConfig(&$config, ContainerInterface $services): void
    {
        if (isset($config['writers'])) {
            foreach ($config['writers'] as $index => $writerConfig) {
                if (! empty($writerConfig['options']['stream'])) {
                    $config['writers'][$index]['options']['stream'] = self::parseVariables(
                        $writerConfig['options']['stream']
                    );
                }
                if (
                    isset($writerConfig['name'])
                    && in_array($writerConfig['name'], ['mail', Mail::class, 'laminaslogwritermail'])
                    && isset($writerConfig['options']['mail_service'])
                    && is_string($writerConfig['options']['mail_service'])
                    && $services->has($writerConfig['options']['mail_service'])
                ) {
                    /** @var MailServiceInterface $mailService */
                    $mailService = $services->get($writerConfig['options']['mail_service']);
                    $mail        = $mailService->getMessage();
                    $transport   = $mailService->getTransport();

                    $config['writers'][$index]['options']['mail']      = $mail;
                    $config['writers'][$index]['options']['transport'] = $transport;
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
