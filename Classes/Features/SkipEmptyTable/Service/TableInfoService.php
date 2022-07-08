<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SkipEmptyTable\Service;

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

use Throwable;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\SingletonInterface;

use function array_column;
use function array_flip;
use function array_merge;
use function array_unique;

class TableInfoService implements SingletonInterface
{
    protected Connection $localDatabase;

    protected Connection $foreignDatabase;

    /** @var array<string, array<string, array<int|bool>>> */
    protected array $tableInfo = [];

    public function __construct(Connection $localDatabase, Connection $foreignDatabase)
    {
        $this->localDatabase = $localDatabase;
        $this->foreignDatabase = $foreignDatabase;
    }

    public function isEmptyTable(string $table): bool
    {
        if (!isset($this->tableInfo[$table])) {
            $this->tableInfo[$table] = $this->queryTableInfo($table);
        }
        /** @psalm-suppress InvalidReturnStatement */
        return $this->tableInfo[$table]['isEmpty'];
    }

    public function isPidInTable(string $table, int $pid): bool
    {
        if (!isset($this->tableInfo[$table])) {
            $this->tableInfo[$table] = $this->queryTableInfo($table);
        }
        return $this->tableInfo[$table]['hasPid'] && isset($this->tableInfo[$table]['uniquePidsIndex'][$pid]);
    }

    protected function queryTableInfo(string $table): array
    {
        $hasPid = isset($GLOBALS['TCA'][$table]);
        if ($hasPid) {
            $localPids = $this->queryTableFromDatabase($this->localDatabase, $table);
            $foreignPids = $this->queryTableFromDatabase($this->foreignDatabase, $table);
            $uniquePids = array_unique(array_merge($localPids, $foreignPids));
            $isEmpty = empty($uniquePids);
        } else {
            $uniquePids = [];
            $isEmpty = $this->isEmpty($this->localDatabase, $table)
                       && $this->isEmpty($this->foreignDatabase, $table);
        }
        return [
            'isEmpty' => $isEmpty,
            'hasPid' => $hasPid,
            'uniquePidsIndex' => array_flip($uniquePids),
        ];
    }

    protected function isEmpty(Connection $connection, string $table): bool
    {
        try {
            $query = 'SELECT 1 FROM ' . $connection->quoteIdentifier($table) . ';';
            $atLeastOneRowExists = $connection->executeQuery($query)->fetchOne();
            return !$atLeastOneRowExists;
        } catch (Throwable $exception) {
            // Ignore any errors.
            // They might indicate that the table does not exist, but that's not this classes' responsibility
        }
        return false;
    }

    protected function queryTableFromDatabase(Connection $connection, string $table): array
    {
        $quotedQuery = $connection->quoteIdentifier($table);
        $rows = $connection->executeQuery('SELECT DISTINCT `pid` FROM ' . $quotedQuery)->fetchAllAssociative();
        return array_column($rows, 'pid');
    }
}
