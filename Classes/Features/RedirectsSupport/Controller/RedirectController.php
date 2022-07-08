<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Controller;

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

use In2code\In2publishCore\Component\TcaHandling\Demand\DemandsFactory;
use In2code\In2publishCore\Component\TcaHandling\Demand\Resolver\DemandResolverCollection;
use In2code\In2publishCore\Component\TcaHandling\Demand\Resolver\JoinDemandResolver;
use In2code\In2publishCore\Component\TcaHandling\Demand\Resolver\SelectDemandResolver;
use In2code\In2publishCore\Component\TcaHandling\Demand\Resolver\SysRedirectSelectDemandResolver;
use In2code\In2publishCore\Component\TcaHandling\Publisher\PublisherService;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;
use In2code\In2publishCore\Controller\AbstractController;
use In2code\In2publishCore\Controller\ActionController;
use In2code\In2publishCore\Controller\Traits\ControllerModuleTemplate;
use In2code\In2publishCore\Domain\Model\RecordTree;
use In2code\In2publishCore\Domain\Service\ForeignSiteFinder;
use In2code\In2publishCore\Features\RedirectsSupport\Backend\Button\BackButton;
use In2code\In2publishCore\Features\RedirectsSupport\Backend\Button\SaveAndPublishButton;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Dto\Filter;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Repository\SysRedirectRepository;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function count;
use function implode;
use function reset;
use function sprintf;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) I probably really need all these classes.
 */
class RedirectController extends ActionController
{
    use ControllerModuleTemplate;

    protected ForeignSiteFinder $foreignSiteFinder;
    protected SysRedirectRepository $sysRedirectRepo;
    protected IconFactory $iconFactory;
    private DemandsFactory $demandsFactory;
    protected DemandResolverCollection $demandResolverCollection;
    protected SelectDemandResolver $selectDemandResolver;
    protected JoinDemandResolver $joinDemandResolver;
    protected SysRedirectSelectDemandResolver $sysRedirectSelectDemandResolver;
    private PublisherService $publisherService;

    public function injectForeignSiteFinder(ForeignSiteFinder $foreignSiteFinder): void
    {
        $this->foreignSiteFinder = $foreignSiteFinder;
    }

    public function injectSysRedirectRepo(SysRedirectRepository $sysRedirectRepo): void
    {
        $this->sysRedirectRepo = $sysRedirectRepo;
    }

    public function injectPageRenderer(PageRenderer $pageRenderer): void
    {
        $pageRenderer->addCssFile(
            'EXT:in2publish_core/Resources/Public/Css/Modules.css',
            'stylesheet',
            'all',
            '',
            false
        );
    }

    public function injectIconFactory(IconFactory $iconFactory): void
    {
        $this->iconFactory = $iconFactory;
    }

    public function injectDemandsFactory(DemandsFactory $demandsFactory): void
    {
        $this->demandsFactory = $demandsFactory;
    }

    public function injectDemandResolverCollection(DemandResolverCollection $demandResolverCollection): void
    {
        $this->demandResolverCollection = $demandResolverCollection;
    }

    public function injectSelectDemandResolver(SelectDemandResolver $selectDemandResolver): void
    {
        $this->selectDemandResolver = $selectDemandResolver;
    }

    public function injectJoinDemandResolver(JoinDemandResolver $joinDemandResolver): void
    {
        $this->joinDemandResolver = $joinDemandResolver;
    }

    public function injectSysRedirectSelectDemandResolver(
        SysRedirectSelectDemandResolver $sysRedirectSelectDemandResolver
    ): void {
        $this->sysRedirectSelectDemandResolver = $sysRedirectSelectDemandResolver;
    }

    public function injectPublisherService(PublisherService $publisherService): void
    {
        $this->publisherService = $publisherService;
    }

    /** @throws Throwable */
    public function initializeListAction(): void
    {
        if ($this->request->hasArgument('filter')) {
            $filter = $this->request->getArgument('filter');
            $this->backendUser->setAndSaveSessionData('tx_in2publishcore_redirects_filter', $filter);
        } else {
            $filter = $this->backendUser->getSessionData('tx_in2publishcore_redirects_filter');
            if (null !== $filter) {
                $this->request->setArgument('filter', $filter);
            }
            $this->arguments->getArgument('filter')->getPropertyMappingConfiguration()->allowAllProperties();
        }
    }

    /**
     * @param Filter|null $filter
     * @param int $page
     * @return ResponseInterface
     * @throws Throwable
     */
    public function listAction(Filter $filter = null, int $page = 1): ResponseInterface
    {
        $additionalWhere = '';
        if (null !== $filter) {
            $additionalWhere = $filter->toAdditionWhere();
        }

        $recordTree = new RecordTree();

        $demands = $this->demandsFactory->createDemand();
        $demands->addSysRedirectSelect('sys_redirect', $additionalWhere, $recordTree);

        $this->demandResolverCollection->addDemandResolver($this->selectDemandResolver);
        $this->demandResolverCollection->addDemandResolver($this->joinDemandResolver);
        $this->demandResolverCollection->addDemandResolver($this->sysRedirectSelectDemandResolver);

        $recordCollection = new RecordCollection();
        $this->demandResolverCollection->resolveDemand($demands, $recordCollection);

        $redirects = $recordTree->getChildren()['sys_redirect'] ?? [];
        $paginator = new ArrayPaginator($redirects, $page, 15);
        $pagination = new SimplePagination($paginator);
        $this->view->assignMultiple(
            [
                'paginator' => $paginator,
                'pagination' => $pagination,
                'hosts' => $this->sysRedirectRepo->findHostsOfRedirects(),
                'statusCodes' => $this->sysRedirectRepo->findStatusCodesOfRedirects(),
                'filter' => $filter,
            ]
        );
        return $this->htmlResponse();
    }

    /** @throws Throwable */
    public function publishAction(array $redirects): void
    {
        if (empty($redirects)) {
            $this->addFlashMessage(
                'No redirect has been selected for publishing',
                'Skipping publishing',
                AbstractMessage::NOTICE
            );
            $this->redirect('list');
        }

        $recordTree = new RecordTree();

        $demands = $this->demandsFactory->createDemand();
        foreach ($redirects as $redirect) {
            $demands->addSelect('sys_redirect', '', 'uid', $redirect, $recordTree);
        }

        $this->demandResolverCollection->addDemandResolver($this->selectDemandResolver);
        $this->demandResolverCollection->addDemandResolver($this->joinDemandResolver);
        $this->demandResolverCollection->addDemandResolver($this->sysRedirectSelectDemandResolver);

        $recordCollection = new RecordCollection();
        $this->demandResolverCollection->resolveDemand($demands, $recordCollection);

        $this->publisherService->publishRecordTree($recordTree);

        if (count($redirects) === 1) {
            $this->addFlashMessage(sprintf('Redirect %s published', reset($redirects)));
        } else {
            $this->addFlashMessage(sprintf('Redirects %s published', implode(', ', $redirects)));
        }
        $this->redirect('list');
    }

    /**
     * @param int $redirect
     * @param array|null $properties
     * @return ResponseInterface
     * @throws Throwable
     */
    public function selectSiteAction(int $redirect, array $properties = null): ResponseInterface
    {
        $redirectDto = $this->sysRedirectRepo->findLocalRawByUid($redirect);
        if (null === $redirectDto) {
            $this->redirect('list');
        }

        if ($this->request->getMethod() === 'POST') {
            $redirectDto->tx_in2publishcore_foreign_site_id = $properties['siteId'];
            $this->sysRedirectRepo->update($redirectDto);
            $this->addFlashMessage(
                sprintf(
                    'Associated redirect %s with site %s',
                    $redirectDto->__toString(),
                    $redirectDto->tx_in2publishcore_foreign_site_id
                )
            );
            if (isset($_POST['_saveandpublish'])) {
                $this->redirect('publish', null, null, ['redirects' => [$redirectDto->uid]]);
            }
            $this->redirect('list');
        }
        $sites = $this->foreignSiteFinder->getAllSites();
        $siteOptions = [
            '*' => LocalizationUtility::translate(
                'LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:source_host_global_text'
            ),
        ];
        foreach ($sites as $site) {
            $identifier = $site->getIdentifier();
            $siteOptions[$identifier] = $identifier . ' (' . $site->getBase() . ')';
        }
        $this->view->assign('redirect', $redirectDto);
        $this->view->assign('siteOptions', $siteOptions);

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $buttonBar->addButton(new BackButton($this->iconFactory, $this->uriBuilder));
        $buttonBar->addButton(new SaveAndPublishButton($this->iconFactory));

        return $this->htmlResponse();
    }
}
