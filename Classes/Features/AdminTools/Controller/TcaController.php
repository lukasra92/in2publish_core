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

use In2code\In2publishCore\Domain\Service\TcaProcessingService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;

class TcaController extends ActionController
{
    public function indexAction(): ResponseInterface
    {
        $this->view->assign('incompatibleTca', TcaProcessingService::getIncompatibleTca());
        $this->view->assign('compatibleTca', TcaProcessingService::getCompatibleTca());
        $this->view->assign('controls', TcaProcessingService::getControls());
        return $this->htmlResponse();
    }

    /** @throws StopActionException */
    public function clearTcaCachesAction(): void
    {
        GeneralUtility::makeInstance(TcaProcessingService::class)->flushCaches();
        $this->redirect('index');
    }
}
