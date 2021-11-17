<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Command\Status;

/*
 * Copyright notice
 *
 * (c) 2019 in2code.de and the following authors:
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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Site\SiteFinder;

use function base64_encode;
use function json_encode;

class ShortSiteConfigurationCommand extends Command
{
    public const IDENTIFIER = 'in2publish_core:status:shortsiteconfiguration';

    protected SiteFinder $siteFinder;

    public function __construct(SiteFinder $siteFinder, string $name = null)
    {
        parent::__construct($name);
        $this->siteFinder = $siteFinder;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $shortInfo = [];
        foreach ($this->siteFinder->getAllSites() as $site) {
            $shortInfo[$site->getIdentifier()] = [
                'base' => $site->getBase()->__toString(),
                'rootPageId' => $site->getRootPageId(),
            ];
        }
        $output->writeln('ShortSiteConfig: ' . base64_encode(json_encode($shortInfo)));
        return Command::SUCCESS;
    }
}
