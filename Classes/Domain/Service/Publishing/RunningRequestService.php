<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Service\Publishing;

/*
 * Copyright notice
 *
 * (c) 2022 in2code.de and the following authors:
 * Christine Zoglmeier <christine.zoglmeier@in2code.de>
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

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\RunningRequestRepository;
use In2code\In2publishCore\Event\DetermineIfRecordIsPublishing;
use In2code\In2publishCore\Event\RecordsWereSelectedForPublishing;
use In2code\In2publishCore\Event\RecursiveRecordPublishingBegan;
use In2code\In2publishCore\Event\VoteIfRecordIsPublishable;
use TYPO3\CMS\Core\SingletonInterface;

use function bin2hex;
use function random_bytes;
use function register_shutdown_function;

class RunningRequestService implements SingletonInterface
{
    /** @var RunningRequestRepository */
    protected $runningRequestRepository;

    protected $requestToken;

    protected $shutdownFunctionRegistered = false;

    public function __construct(RunningRequestRepository $runningRequestRepository)
    {
        $this->runningRequestRepository = $runningRequestRepository;
        $this->requestToken = bin2hex(random_bytes(16));
    }

    public function onRecursiveRecordPublishingBegan(RecursiveRecordPublishingBegan $event): void
    {
        $this->registerShutdownFunction();

        /** @var Record $record */
        $record = $event->getRecord();

        $this->writeToRunningRequestsTable($record);
        $this->runningRequestRepository->flush();
    }

    public function onRecordsWereSelectedForPublishing(RecordsWereSelectedForPublishing $event): void
    {
        $this->registerShutdownFunction();

        foreach ($event->getRecords() as $record) {
            $this->writeToRunningRequestsTable($record);
        }
        $this->runningRequestRepository->flush();
    }

    protected function writeToRunningRequestsTable(RecordInterface $record): void
    {
        $recordId = (string)$record->getIdentifier();
        $tableName = $record->getTableName();

        $this->runningRequestRepository->add($recordId, $tableName, $this->requestToken);

        foreach ($record->getRelatedRecords() as $relatedRecords) {
            foreach ($relatedRecords as $relatedRecord) {
                $this->writeToRunningRequestsTable($relatedRecord);
            }
        }
    }

    public function isPublishable(VoteIfRecordIsPublishable $event): void
    {
        $id = $event->getIdentifier();
        $table = $event->getTable();
        if ($this->runningRequestRepository->isPublishingInDifferentRequest($id, $table, $this->requestToken)) {
            $event->voteNo();
        }
    }

    public function isPublishing(DetermineIfRecordIsPublishing $event): void
    {
        $id = $event->getIdentifier();
        $table = $event->getTableName();
        if ($this->runningRequestRepository->isPublishingInDifferentRequest($id, $table, $this->requestToken)) {
            $event->setIsPublishing();
        }
    }

    protected function registerShutdownFunction(): void
    {
        if (!$this->shutdownFunctionRegistered) {
            $repository = $this->runningRequestRepository;
            $token = $this->requestToken;
            register_shutdown_function(static function () use ($repository, $token) {
                $repository->deleteAllByToken($token);
            });
            $this->shutdownFunctionRegistered = true;
        }
    }
}
