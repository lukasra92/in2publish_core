<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Config\Node;

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

use In2code\In2publishCore\Config\ValidationContainer;

interface Node
{
    public const T_ARRAY = 'array';
    public const T_STRICT_ARRAY = 'strict_array';
    public const T_STRING = 'string';
    public const T_OPTIONAL_STRING = 'optional_string';
    public const T_INTEGER = 'integer';
    public const T_BOOLEAN = 'boolean';

    /** @param mixed $value */
    public function validate(ValidationContainer $container, $value): void;

    public function getName(): string;

    public function addNode(Node $node): void;

    public function getNodePath(string $path): Node;

    /** @return string[]|int[]|bool[]|array[] */
    public function getDefaults(): array;

    public function merge(Node $node): void;

    public function mergeArrays(array $original, array $additional): array;

    /** @param mixed $value */
    public function cast($value);

    /** @param string[]|int[]|bool[]|array[] $value */
    public function unsetDefaults(array &$value): void;
}
