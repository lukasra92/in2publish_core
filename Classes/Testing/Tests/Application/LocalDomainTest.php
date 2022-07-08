<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Application;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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

use In2code\In2publishCore\Testing\Tests\Database\LocalDatabaseTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Site\SiteFinder;

class LocalDomainTest extends AbstractDomainTest implements TestCaseInterface
{
    protected Connection $localConnection;
    protected SiteFinder $siteFinder;
    protected string $prefix = 'local';

    public function __construct(SiteFinder $siteFinder)
    {
        $this->localConnection = DatabaseUtility::buildLocalDatabaseConnection();
        $this->siteFinder = $siteFinder;
    }

    protected function getPageToSiteBaseMapping(): array
    {
        $shortInfo = [];
        foreach ($this->siteFinder->getAllSites() as $site) {
            $shortInfo[$site->getRootPageId()] = $site->getBase()->__toString();
        }
        return $shortInfo;
    }

    protected function getConnection(): Connection
    {
        return $this->localConnection;
    }

    public function getDependencies(): array
    {
        return [
            LocalDatabaseTest::class,
        ];
    }
}
