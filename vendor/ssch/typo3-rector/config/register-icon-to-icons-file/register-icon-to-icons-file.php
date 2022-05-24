<?php

declare (strict_types=1);
namespace RectorPrefix20220524;

use Rector\Config\RectorConfig;
use Ssch\TYPO3Rector\Rector\v11\v5\RegisterIconToIconFileRector;
return static function (\Rector\Config\RectorConfig $rectorConfig) : void {
    $rectorConfig->import(__DIR__ . '/../config.php');
    $rectorConfig->rule(\Ssch\TYPO3Rector\Rector\v11\v5\RegisterIconToIconFileRector::class);
};
