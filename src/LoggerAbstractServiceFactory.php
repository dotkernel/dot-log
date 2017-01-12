<?php
/**
 * @copyright: DotKernel
 * @library: dotkernel/dot-log
 * @author: n3vrax
 * Date: 1/12/2017
 * Time: 11:47 PM
 */

namespace Dot\Log;

use Dot\Mail\Service\MailServiceInterface;
use Interop\Container\ContainerInterface;
use Zend\Log\Writer\Mail;

/**
 * Class LoggerAbstractServiceFactory
 * @package Dot\Log
 */
class LoggerAbstractServiceFactory extends \Zend\Log\LoggerAbstractServiceFactory
{
    /** @var string  */
    protected $configKey = 'dot_log';

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
