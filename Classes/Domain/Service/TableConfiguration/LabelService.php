<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Service\TableConfiguration;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Service\Configuration\TcaService;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

use function sprintf;
use function trim;

class LabelService
{
    /** @var string */
    protected $emptyFieldValue = '---';

    /** @var TcaService */
    protected $tcaService;

    public function __construct(TcaService $tcaService)
    {
        $this->tcaService = $tcaService;
    }

    /**
     * Get label field from record
     *
     * @param RecordInterface $record
     * @param string $stagingLevel "local" or "foreign"
     *
     * @return string
     */
    public function getLabelField(RecordInterface $record, string $stagingLevel = 'local'): string
    {
        $table = $record->getTableName();

        if ($table === 'sys_file_reference') {
            return sprintf(
                '%d [%d,%d]',
                $record->getPropertyBySideIdentifier($stagingLevel, 'uid'),
                $record->getPropertyBySideIdentifier($stagingLevel, 'uid_local'),
                $record->getPropertyBySideIdentifier($stagingLevel, 'uid_foreign')
            );
        }
        $row = ObjectAccess::getProperty($record, $stagingLevel . 'Properties');
        if (empty($row)) {
            return $this->emptyFieldValue;
        }
        $label = $this->tcaService->getRecordLabel($row, $table);
        if (trim($label) === '') {
            $label = $this->emptyFieldValue;
        }
        return $label;
    }
}
