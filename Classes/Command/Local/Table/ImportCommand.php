<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Command\Local\Table;

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

use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Service\Database\DatabaseSchemaService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;

use function sprintf;

class ImportCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const ARG_TABLE_NAME = 'tableName';
    public const ARG_TABLE_NAME_DESCRIPTION = 'The table to back up';
    public const EXIT_INVALID_TABLE = 220;
    public const IDENTIFIER = 'in2publish_core:table:import';
    protected Connection $localDatabase;
    private Connection $foreignDatabase;
    private ContextService $contextService;
    private DatabaseSchemaService $databaseSchemaService;

    public function injectLocalDatabase(Connection $localDatabase): void
    {
        $this->localDatabase = $localDatabase;
    }

    public function injectForeignDatabase(Connection $foreignDatabase): void
    {
        $this->foreignDatabase = $foreignDatabase;
    }

    public function injectContextService(ContextService $contextService): void
    {
        $this->contextService = $contextService;
    }

    public function injectDatabaseSchemaService(DatabaseSchemaService $databaseSchemaService): void
    {
        $this->databaseSchemaService = $databaseSchemaService;
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARG_TABLE_NAME, InputArgument::REQUIRED, self::ARG_TABLE_NAME_DESCRIPTION);
    }

    public function isEnabled(): bool
    {
        return $this->contextService->isLocal();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $tableName = $input->getArgument(self::ARG_TABLE_NAME);

        if (!$this->databaseSchemaService->tableExists($tableName)) {
            $errOutput->writeln(sprintf('The table "%s" does not exist', $tableName));
            $this->logger->error(
                'The table that should be backed up before import does not exist',
                ['table' => $tableName]
            );
            return static::EXIT_INVALID_TABLE;
        }

        $this->logger->notice('Called Import Table Command for table', ['table' => $tableName]);

        DatabaseUtility::backupTable($this->localDatabase, $tableName);

        try {
            $rowCount = DatabaseUtility::copyTableContents(
                $this->foreignDatabase,
                $this->localDatabase,
                $tableName
            );
            $this->logger->notice('Successfully truncated table, importing rows', ['rowCount' => $rowCount]);
            $this->logger->notice('Finished importing of table', ['table' => $tableName]);
        } catch (In2publishCoreException $exception) {
            $this->logger->critical(
                'Could not truncate local table. Skipping import',
                ['table' => $tableName, 'exception' => $exception]
            );
            $errOutput->writeln(sprintf('Could not truncate local table "%s". Skipping import', $tableName));
        }

        return Command::SUCCESS;
    }
}
