<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\AdminTools\Controller;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\Event\CreatedDefaultHelpLabels;
use In2code\In2publishCore\Features\AdminTools\Service\ToolsRegistry;
use In2code\In2publishCore\Service\Environment\EnvironmentService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function implode;

use const PHP_EOL;

class ToolsController extends ActionController
{
    /** @var EnvironmentService */
    protected $environmentService;

    /** @var ToolsRegistry */
    protected $toolsRegistry;

    public function __construct(EnvironmentService $environmentService, ToolsRegistry $toolsRegistry)
    {
        $this->environmentService = $environmentService;
        $this->toolsRegistry = $toolsRegistry;
    }

    public function indexAction(): void
    {
        $testStates = $this->environmentService->getTestStatus();

        $messages = [];
        foreach ($testStates as $testState) {
            $messages[] = LocalizationUtility::translate('test_state_error.' . $testState, 'in2publish_core');
        }
        if (!empty($messages)) {
            $this->addFlashMessage(
                implode(PHP_EOL, $messages),
                LocalizationUtility::translate('test_state_error', 'in2publish_core'),
                AbstractMessage::ERROR
            );
        }

        $supports = [
            LocalizationUtility::translate('help.github_issues', 'in2publish_core'),
            LocalizationUtility::translate('help.slack_channel', 'in2publish_core'),
        ];

        $event = new CreatedDefaultHelpLabels($supports);
        $this->eventDispatcher->dispatch($event);
        $supports = $event->getSupports();

        $this->view->assign('supports', $supports);

        $this->view->assign('tools', $this->toolsRegistry->getEntries());
    }
}
