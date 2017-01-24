<?php
/**
 * @copyright: DotKernel
 * @library: dotkernel/dot-log
 * @author: n3vrax
 * Date: 1/12/2017
 * Time: 11:47 PM
 */

namespace Dot\Log\Factory;

use Dot\Mail\Service\MailServiceInterface;
use Interop\Container\ContainerInterface;
use Zend\Log\Writer\Mail;

/**
 * Class LoggerAbstractServiceFactory
 * @package Dot\Log
 */
class LoggerAbstractServiceFactory extends \Zend\Log\LoggerAbstractServiceFactory
{
    const PREFIX = 'dot-log';

    /** @var string */
    protected $configKey = 'dot_log';

    /** @var string  */
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
     * @return object|\Zend\Log\Logger
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
    protected function getConfig(ContainerInterface $services)
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
        parent::processConfig($config, $services);

        if (!isset($config['writers'])) {
            return;
        }

        foreach ($config['writers'] as $index => $writerConfig) {
            if (isset($writerConfig['name'])
                && ('mail' === $writerConfig['name']
                    || Mail::class === $writerConfig['name']
                    || 'zendlogwritermail' === $writerConfig['name']
                )
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
                continue;
            }
        }
    }
}
