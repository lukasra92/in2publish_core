<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Command\Foreign\Status;

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

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Config\ValidationContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_column;
use function base64_encode;
use function json_encode;

class ConfigFormatTestCommand extends Command
{
    public const IDENTIFIER = 'in2publish_core:status:configformattest';
    protected ValidationContainer $validationContainer;
    protected ConfigContainer $configContainer;

    public function injectValidationContainer(ValidationContainer $validationContainer): void
    {
        $this->validationContainer = $validationContainer;
    }

    public function injectConfigContainer(ConfigContainer $configContainer): void
    {
        $this->configContainer = $configContainer;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $definition = $this->configContainer->getForeignDefinition();
        $actual = $this->configContainer->get();
        $definition->validate($this->validationContainer, $actual);
        $errors = $this->validationContainer->getErrors();
        $value = base64_encode(json_encode(array_column($errors, 'configuration'), JSON_THROW_ON_ERROR));
        $output->writeln('Config Format Test: ' . $value);
        return Command::SUCCESS;
    }
}
