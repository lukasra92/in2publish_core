<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Config\PostProcessor\DynamicValueProvider;

/*
 * Copyright notice
 *
 * (c) 2020 in2code.de and the following authors:
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

use In2code\In2publishCore\Config\PostProcessor\DynamicValueProvider\Exception\InvalidDynamicValueProviderKeyException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DynamicValueProviderRegistry implements SingletonInterface
{
    /** @var string[] */
    protected $classes = [];

    /** @var DynamicValueProviderInterface[] */
    protected $objects = [];

    /**
     * @param string $key The key which will be used in the configuration to call the registered provider
     * @param string $class The FQCN of the provider. Must implement `DynamicValueProviderInterface`.
     */
    public function registerDynamicValue(string $key, string $class): void
    {
        $this->classes[$key] = $class;
    }

    public function getRegisteredClasses(): array
    {
        return $this->classes;
    }

    public function hasDynamicValueProviderForKey(string $key): bool
    {
        return isset($this->classes[$key]);
    }

    public function getDynamicValueProviderByKey(string $key): DynamicValueProviderInterface
    {
        if (!$this->hasDynamicValueProviderForKey($key)) {
            throw new InvalidDynamicValueProviderKeyException($key);
        }
        if (!isset($this->objects[$key])) {
            $this->objects[$key] = GeneralUtility::makeInstance($this->classes[$key]);
        }
        return $this->objects[$key];
    }
}
