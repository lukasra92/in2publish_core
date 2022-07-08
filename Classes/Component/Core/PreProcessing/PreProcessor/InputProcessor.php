<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_intersect;

class InputProcessor extends TextProcessor
{
    protected string $type = 'input';
    protected array $required = [
        'softref' => 'Only input fields with softref "typolink" or "typolink_tag" can hold relations',
    ];

    protected function additionalPreProcess(string $table, string $column, array $tca): array
    {
        if (!isset($tca['softref'])) {
            return [];
        }
        $softRef = GeneralUtility::trimExplode(',', $tca['softref'] ?? '', true);
        if (empty(array_intersect(['typolink', 'typolink_tag'], $softRef))) {
            return ['Only input fields with typolinks can hold relations'];
        }
        return [];
    }
}
