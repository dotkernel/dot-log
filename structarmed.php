<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())

    ->layer('Log', [
        'src/ConfigProvider.php',
        'src/Logger.php',
        'src/LoggerServiceFactory.php',
    ])
    ->layer('Exception', 'src/Exception/')
    ->layer('Factory', 'src/Factory/')
    ->layer('Filter', 'src/Filter/')
    ->layer('Formatter', 'src/Formatter/')
    ->layer('Manager', 'src/Manager/')
    ->layer('Processor', 'src/Processor/')
    ->layer('Writer', 'src/Writer/')
    ->ruleset([
        'Exception' => [],
        'Processor' => [],
        'Filter'    => ['Exception'],
        'Formatter' => ['Exception'],
        'Writer'    => ['+Filter', '+Formatter', 'Manager'],
        'Manager'   => ['+Writer', 'Processor', 'Factory'],
        'Factory'   => ['+Log'],
        'Log'       => ['+Manager'],
    ]);
