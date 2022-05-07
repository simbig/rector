<?php

declare (strict_types=1);
namespace RectorPrefix20220507;

use Rector\Config\RectorConfig;
use Ssch\TYPO3Rector\Rector\v11\v3\SubstituteExtbaseRequestGetBaseUriRector;
use Ssch\TYPO3Rector\Rector\v11\v3\SubstituteMethodRmFromListOfGeneralUtilityRector;
use Ssch\TYPO3Rector\Rector\v11\v3\SwitchBehaviorOfArrayUtilityMethodsRector;
use Ssch\TYPO3Rector\Rector\v11\v3\UseLanguageTypeForLanguageFieldColumnRector;
return static function (\Rector\Config\RectorConfig $rectorConfig) : void {
    $rectorConfig->import(__DIR__ . '/../config.php');
    $rectorConfig->rule(\Ssch\TYPO3Rector\Rector\v11\v3\SubstituteMethodRmFromListOfGeneralUtilityRector::class);
    $rectorConfig->rule(\Ssch\TYPO3Rector\Rector\v11\v3\SwitchBehaviorOfArrayUtilityMethodsRector::class);
    $rectorConfig->rule(\Ssch\TYPO3Rector\Rector\v11\v3\UseLanguageTypeForLanguageFieldColumnRector::class);
    $rectorConfig->rule(\Ssch\TYPO3Rector\Rector\v11\v3\SubstituteExtbaseRequestGetBaseUriRector::class);
};
