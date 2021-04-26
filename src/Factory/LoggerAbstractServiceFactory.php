<?php
/**
 * @see https://github.com/dotkernel/dot-log/ for the canonical source repository
 * @copyright Copyright (c) 2017 Apidemia (https://www.apidemia.com)
 * @license https://github.com/dotkernel/dot-log/blob/master/LICENSE.md MIT License
 */

declare(strict_types = 1);

namespace Dot\Log\Factory;

use Dot\Mail\Service\MailServiceInterface;
use Interop\Container\ContainerInterface;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Mail;

use function count;
use function date;
use function explode;
use function is_array;
use function is_string;
use function preg_match_all;
use function str_replace;

/**
 * Class LoggerAbstractServiceFactory
 * @package Dot\Log
 */
class LoggerAbstractServiceFactory extends \Laminas\Log\LoggerAbstractServiceFactory
{
    const PREFIX = 'dot-log';

    /** @var string */
    protected $configKey = 'dot_log';

    /** @var string */
    protected $subConfigKey = 'loggers';

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
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
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return object|Logger
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $parts = explode('.', $requestedName);
        return parent::__invoke($container, $parts[1], $options);
    }

    /**
     * @param ContainerInterface $services
     * @return array
     */
    protected function getConfig(ContainerInterface $services): array
    {
        parent::getConfig($services);

        if (!empty($this->config)
            && isset($this->config[$this->subConfigKey])
            && is_array($this->config[$this->subConfigKey])
        ) {
            $this->config = $this->config[$this->subConfigKey];
        }

        return $this->config;
    }

    /**
     * @param array $config
     * @param ContainerInterface $services
     */
    protected function processConfig(&$config, ContainerInterface $services)
    {
        if (isset($config['writers'])) {
            foreach ($config['writers'] as $index => $writerConfig) {
                if (!empty($writerConfig['options']['stream'])) {
                    $config['writers'][$index]['options']['stream'] = self::parseVariables(
                        $writerConfig['options']['stream']
                    );
                }
                if (isset($writerConfig['name'])
                    && in_array($writerConfig['name'], ['mail', Mail::class, 'laminaslogwritermail'])
                    && isset($writerConfig['options']['mail_service'])
                    && is_string($writerConfig['options']['mail_service'])
                    && $services->has($writerConfig['options']['mail_service'])
                ) {
                    /** @var MailServiceInterface $mailService */
                    $mailService = $services->get($writerConfig[['options']['mail_service']]);
                    $mail = $mailService->getMessage();
                    $transport = $mailService->getTransport();

                    $config['writers'][$index]['options']['mail'] = $mail;
                    $config['writers'][$index]['options']['transport'] = $transport;
                }
            }
        }

        parent::processConfig($config, $services);
    }

    /**
     * @param string $stream
     * @return string
     */
    private static function parseVariables(string $stream): string
    {
        preg_match_all('/{([a-z])}/i', $stream, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $stream = str_replace('{' . $match . '}', date($match), $stream);
            }
        }

        return $stream;
    }
}
