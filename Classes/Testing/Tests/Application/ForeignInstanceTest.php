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

use In2code\In2publishCore\Command\Foreign\Status\AllCommand;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Testing\Tests\Adapter\RemoteAdapterTest;
use In2code\In2publishCore\Testing\Tests\Database\ForeignDatabaseTest;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function strpos;

class ForeignInstanceTest implements TestCaseInterface
{
    protected RemoteCommandDispatcher $rceDispatcher;
    protected Typo3Version $typo3Version;

    public function __construct(RemoteCommandDispatcher $remoteCommandDispatcher, Typo3Version $typo3Version)
    {
        $this->rceDispatcher = $remoteCommandDispatcher;
        $this->typo3Version = $typo3Version;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function run(): TestResult
    {
        $request = new RemoteCommandRequest(AllCommand::IDENTIFIER);
        $response = $this->rceDispatcher->dispatch($request);

        if (!$response->isSuccessful()) {
            if (false !== strpos($response->getOutputString(), '_cli_lowlevel')) {
                return new TestResult(
                    'application.foreign_cli_lowlevel_user_missing',
                    TestResult::ERROR,
                    ['application.foreign_cli_lowlevel_user_missing_message', $response->getOutputString()]
                );
            }

            if (false !== strpos($response->getOutputString(), 'Could not open input file')) {
                return new TestResult(
                    'application.foreign_cli_dispatcher_wrong_path',
                    TestResult::ERROR,
                    [
                        'application.foreign_cli_dispatcher_error_message',
                        $response->getOutputString(),
                        $response->getErrorsString(),
                    ]
                );
            }

            return new TestResult(
                'application.foreign_cli_dispatcher_not_callable',
                TestResult::ERROR,
                [
                    'application.foreign_cli_dispatcher_error_message',
                    $response->getOutputString(),
                    $response->getErrorsString(),
                ]
            );
        }

        $foreign = $this->tokenizeResponse($response->getOutput());

        $localVersion = ExtensionUtility::getExtensionVersion('in2publish_core');
        if (!isset($foreign['Version'])) {
            return new TestResult(
                'application.foreign_version_not_detectable',
                TestResult::ERROR,
                [
                    'application.foreign_cli_dispatcher_error_message',
                    $response->getOutputString(),
                    $response->getErrorsString(),
                ]
            );
        }

        if ($foreign['Version'] !== $localVersion) {
            return new TestResult(
                'application.foreign_version_differs',
                TestResult::ERROR,
                ['application.local_version', $localVersion, 'application.foreign_version', $foreign['Version']]
            );
        }

        $localT3Version = $this->typo3Version->getVersion();
        if ($foreign['TYPO3'] !== $localT3Version) {
            return new TestResult(
                'application.different_t3_versions',
                TestResult::ERROR,
                ['application.local_t3_versions', $localT3Version, 'application.foreign_t3_versions', $foreign['TYPO3']]
            );
        }

        if (isset($foreign['Utf8Filesystem']) && '1' === $foreign['Utf8Filesystem']) {
            return new TestResult(
                'application.foreign_utf8_fs',
                TestResult::ERROR,
                ['application.utf8_fs_errors']
            );
        }

        if (isset($foreign['adminOnly']) && '0' === $foreign['adminOnly']) {
            return new TestResult(
                'application.foreign_admin_mode',
                TestResult::WARNING,
                ['application.editor_login_possible']
            );
        }

        return new TestResult('application.foreign_system_validated');
    }

    protected function tokenizeResponse(array $output): array
    {
        $values = [];
        foreach ($output as $line) {
            if (false !== strpos($line, ':')) {
                [$key, $value] = GeneralUtility::trimExplode(':', $line);
                $values[$key] = $value;
            }
        }
        return $values;
    }

    public function getDependencies(): array
    {
        return [
            RemoteAdapterTest::class,
            ForeignDatabaseTest::class,
        ];
    }
}
