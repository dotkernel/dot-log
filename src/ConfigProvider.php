<?php
/**
 * @copyright: DotKernel
 * @library: dotkernel/dot-frontend
 * @author: n3vrax
 * Date: 1/11/2017
 * Time: 8:44 PM
 */

namespace Dot\Log;

/**
 * Class ConfigProvider
 * @package Dot\Log
 */
class ConfigProvider
{
    /**
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),

            'dot_log' => [

            ],
        ];
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [

        ];
    }
}
