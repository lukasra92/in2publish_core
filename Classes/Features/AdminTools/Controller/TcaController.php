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

use In2code\In2publishCore\Component\TcaHandling\Demand\DemandBuilder;
use In2code\In2publishCore\Component\TcaHandling\DemandResolver\Select\SelectDemandResolver;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Features\AdminTools\Controller\Traits\AdminToolsModuleTemplate;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class TcaController extends ActionController
{
    use AdminToolsModuleTemplate;

    protected TcaPreProcessingService $tcaPreProcessingService;
    protected DemandBuilder $demandBuilder;
    protected SelectDemandResolver $queryService;
    protected ConfigContainer $configContainer;

    public function __construct(TcaPreProcessingService $tcaPreProcessingService)
    {
        $this->tcaPreProcessingService = $tcaPreProcessingService;
    }

    public function injectDemandBuilder(DemandBuilder $demandBuilder): void
    {
        $this->demandBuilder = $demandBuilder;
    }

    public function injectQueryService(SelectDemandResolver $queryService): void
    {
        $this->queryService = $queryService;
    }

    public function injectConfigContainer(ConfigContainer $configContainer): void
    {
        $this->configContainer = $configContainer;
    }

    public function indexAction(): ResponseInterface
    {
        $this->view->assign('incompatibleTca', $this->tcaPreProcessingService->getIncompatibleTcaParts());
        $this->view->assign('compatibleTca', $this->tcaPreProcessingService->getCompatibleTcaParts());

        return $this->htmlResponse();
    }
}
