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

use In2code\In2publishCore\Controller\AbstractController;
use In2code\In2publishCore\Controller\Traits\ControllerModuleTemplate;
use In2code\In2publishCore\Domain\Service\ForeignSiteFinder;
use In2code\In2publishCore\Features\RedirectsSupport\Backend\Button\SaveAndPublishButton;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Dto\Filter;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Repository\SysRedirectRepository;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function array_column;
use function count;
use function implode;
use function reset;
use function sprintf;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) I probably really need all these classes.
 */
class RedirectController extends AbstractController
{
    use ControllerModuleTemplate;

    protected ForeignSiteFinder $foreignSiteFinder;
    protected SysRedirectRepository $sysRedirectRepo;
    protected IconFactory $iconFactory;
    protected LanguageService $languageService;

    public function __construct()
    {
        parent::__construct();
        $this->languageService = $GLOBALS['LANG'];
    }

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
        $foreignConnection = DatabaseUtility::buildForeignDatabaseConnection();
        $uidList = [];
        if (null !== $foreignConnection) {
            $query = $foreignConnection->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->select('uid')->from('sys_redirect')->where($query->expr()->eq('deleted', 1));
            $uidList = array_column($query->execute()->fetchAllAssociative(), 'uid');
        }
        $redirects = $this->sysRedirectRepo->findForPublishing($uidList, $filter);
        $paginator = new QueryResultPaginator($redirects, $page, 15);
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

        foreach ($redirects as &$redirect) {
            $redirect = (int)$redirect;
        }
        unset($redirect);

        $records = [];
        foreach ($redirects as $redirect) {
            $record = $this->recordFinder->findRecordByUidForPublishing($redirect, 'sys_redirect');
            if (null !== $record) {
                $records[] = $record;
            }
        }

        foreach ($records as $record) {
            $this->recordPublisher->publishRecordRecursive($record);
        }

        $this->runTasks();
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
        $redirectObj = $this->sysRedirectRepo->findUnrestrictedByIdentifier($redirect);
        if (null === $redirectObj) {
            $this->redirect('list');
        }

        if ($this->request->getMethod() === 'POST') {
            $redirectObj->setSiteId($properties['siteId']);
            $this->sysRedirectRepo->update($redirectObj);
            $this->addFlashMessage(
                sprintf('Associated redirect %s with site %s', $redirectObj->__toString(), $redirectObj->getSiteId())
            );
            if (isset($_POST['_saveandpublish'])) {
                $this->redirect('publish', null, null, ['redirects' => [$redirectObj->getUid()]]);
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
        $this->view->assign('redirect', $redirectObj);
        $this->view->assign('siteOptions', $siteOptions);

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $button = $buttonBar->makeLinkButton();
        $button->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL));
        $button->setClasses('btn btn-sm');
        $button->setHref($this->uriBuilder->reset()->uriFor('list'));
        $title = $this->languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:back');
        $button->setTitle($title);
        $buttonBar->addButton($button);
        $buttonBar->addButton(new SaveAndPublishButton($this->iconFactory));

        return $this->htmlResponse();
    }
}
