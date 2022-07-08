<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\Node\Specific;

/*
 * Copyright notice
 *
 * (c) 2018 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\ConfigContainer\Node\Node;
use In2code\In2publishCore\Component\ConfigContainer\ValidationContainer;
use TYPO3\CMS\Core\Utility\ArrayUtility;

use function array_search;
use function in_array;
use function is_array;

class SpecArray extends AbsSpecNode
{
    /** @param mixed $value */
    public function validateType(ValidationContainer $container, $value): void
    {
        if (!is_array($value)) {
            $container->addError('The value is not an array');
        }
    }

    /** @return string[]|int[]|bool[]|array[] */
    public function getDefaults(): array
    {
        $defaults = [];
        if (null !== $this->default) {
            $defaults = [$this->name => $this->default];
        }
        $nodeDefaults = $this->nodes->getDefaults();
        if (!isset($defaults[$this->name])) {
            $defaults[$this->name] = [];
        }
        ArrayUtility::mergeRecursiveWithOverrule($defaults[$this->name], $nodeDefaults);
        return $defaults;
    }

    /**
     * @param mixed $value
     *
     * @return array<Node>
     */
    public function cast($value): array
    {
        return $this->nodes->cast($value);
    }

    /** @param array[]|bool[]|int[]|string[] $value */
    public function unsetDefaults(array &$value): void
    {
        $this->nodes->unsetDefaults($value[$this->name]);
        if (null !== $this->default) {
            if ($this->name === 'definition') {
                $newValue = [];
                foreach ($this->default as $key => $defValue) {
                    if (!in_array($defValue, $value[$this->name], true)) {
                        $newValue[$this->name][$key] = '__UNSET';
                    }
                }
                foreach ($value[$this->name] as $var) {
                    $newValue[$this->name][] = $var;
                }
                $value = $newValue;
            } else {
                foreach ($this->default as $defValue) {
                    if (in_array($defValue, $value[$this->name], true)) {
                        unset($value[$this->name][array_search($defValue, $value[$this->name])]);
                    }
                }
            }
        }
        if (empty($value[$this->name])) {
            unset($value[$this->name]);
        }
    }
}
