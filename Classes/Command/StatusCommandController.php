<?php
namespace In2code\In2publishCore\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class StatusCommandController (always enabled)
 */
class StatusCommandController extends AbstractCommandController
{
    const ALL_COMMAND = 'status:all';
    const VERSION_COMMAND = 'status:version';
    const CONFIGURATION_COMMAND = 'status:configuration';
    const CONFIGURATION_RAW_COMMAND = 'status:configurationraw';
    const CREATE_MASKS_COMMAND = 'status:createmasks';
    const GLOBAL_CONFIGURATION = 'status:globalconfiguration';
    const TYPO3_VERSION = 'status:typo3version';

    /**
     * Prints all information about the in2publish system
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @return void
     * @internal
     */
    public function allCommand()
    {
        $this->versionCommand();
        $this->configurationCommand();
        $this->createMasksCommand();
    }

    /**
     * Prints the current version of in2publish
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @throws \TYPO3\CMS\Core\Package\Exception
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     * @internal
     */
    public function versionCommand()
    {
        $EM_CONF = array();
        $_EXTKEY = 'in2publish_core';
        require_once(ExtensionManagementUtility::extPath($_EXTKEY, 'ext_emconf.php'));
        $this->outputLine('Version: ' . $EM_CONF[$_EXTKEY]['version']);
    }

    /**
     * Prints if the configuration was loaded successfully
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @internal
     */
    public function configurationCommand()
    {
        $this->outputLine(
            'ConfigurationState: '
            . LocalizationUtility::translate(ConfigurationUtility::getLoadingState(), 'in2publish_core')
        );
    }

    /**
     * Prints the configuration state as it was received by the condigfuration utility
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @internal
     */
    public function configurationRawCommand()
    {
        $this->outputLine(
            'ConfigurationState: ' . ConfigurationUtility::getLoadingState()
        );
    }

    /**
     * Prints the configured fileCreateMask and folderCreateMask
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @internal
     */
    public function createMasksCommand()
    {
        $isAtLeast76 = GeneralUtility::compat_version('7.6');
        if ($isAtLeast76 === true) {
            $fileCreateMask = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'];
            $folderCreateMask = $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'];
        } else {
            $fileCreateMask = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileCreateMask'];
            $folderCreateMask = $GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'];
        }

        $this->outputLine('FileCreateMask: ' . $fileCreateMask);
        $this->outputLine('FolderCreateMask: ' . $folderCreateMask);
    }

    /**
     * Prints global configuration values
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @internal
     */
    public function globalConfigurationCommand()
    {
        $this->outputLine(
            'Utf8Filesystem: '
            . (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem'])
                ? 'empty'
                : $GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']
            )
        );
    }

    /**
     * Prints TYPO3 version
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @internal
     */
    public function typo3VersionCommand()
    {
        $this->outputLine(TYPO3_version);
    }
}
