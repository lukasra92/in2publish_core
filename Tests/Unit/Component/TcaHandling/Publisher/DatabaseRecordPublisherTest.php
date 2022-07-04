<?php

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\Publisher;

use In2code\In2publishCore\Component\TcaHandling\Publisher\DatabaseRecordPublisher;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Model\FileRecord;
use In2code\In2publishCore\Tests\UnitTestCase;
use TYPO3\CMS\Core\Database\Connection;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\TcaHandling\Publisher\DatabaseRecordPublisher
 */
class DatabaseRecordPublisherTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     * @covers ::canPublish
     */
    public function testCanPublishReturnsTrueForDatabaseRecordsOnly()
    {
        $databaseRecordPublisher = new DatabaseRecordPublisher();

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $this->assertTrue($databaseRecordPublisher->canPublish($databaseRecord));

        $fileRecord = $this->createMock(FileRecord::class);
        $this->assertFalse($databaseRecordPublisher->canPublish($fileRecord));
    }

    /**
     * @covers ::publish
     */
    public function testPublishInsertsAddedRecords()
    {
        $databaseRecordPublisher = new DatabaseRecordPublisher();
        $foreignDatabase =  $this->createMock(Connection::class);
        $foreignDatabase->expects($this->once())->method('insert')->with(
            'tx_in2publishcore_domain_model_test',
            ['foo' => 'bar']
        );
        $databaseRecordPublisher->injectForeignDatabase($foreignDatabase);

        $addedRecord = $this->createMock(DatabaseRecord::class);
        $addedRecord->method('getLocalProps')->willReturn(['foo' => 'bar']);
        $addedRecord->method('getClassification')->willReturn('tx_in2publishcore_domain_model_test');
        $addedRecord->method('getState')->willReturn(\In2code\In2publishCore\Domain\Model\Record::S_ADDED);

        $databaseRecordPublisher->publish($addedRecord);
    }
    /**
     * @covers ::publish
     */
    public function testPublishDeletesRemovedRecords()
    {
        $databaseRecordPublisher = new DatabaseRecordPublisher();
        $foreignDatabase =  $this->createMock(Connection::class);
        $foreignDatabase->expects($this->once())->method('delete')->with(
            'tx_in2publishcore_domain_model_test',
            ['foo' => 'bar']
        );
        $databaseRecordPublisher->injectForeignDatabase($foreignDatabase);

        $addedRecord = $this->createMock(DatabaseRecord::class);
        $addedRecord->method('getForeignIdentificationProps')->willReturn(['foo' => 'bar']);
        $addedRecord->method('getClassification')->willReturn('tx_in2publishcore_domain_model_test');
        $addedRecord->method('getState')->willReturn(\In2code\In2publishCore\Domain\Model\Record::S_DELETED);

        $databaseRecordPublisher->publish($addedRecord);
    }

    /**
     * @covers ::publish
     */
    public function testPublishUpdatesChangedRecords()
    {
        $databaseRecordPublisher = new DatabaseRecordPublisher();
        $foreignDatabase =  $this->createMock(Connection::class);
        $foreignDatabase->expects($this->once())->method('update')->with(
            'tx_in2publishcore_domain_model_test',
            ['prop1' => 'localValue', 'prop2' => 'localValue']
        );
        $databaseRecordPublisher->injectForeignDatabase($foreignDatabase);

        $addedRecord = $this->createMock(DatabaseRecord::class);
        $addedRecord->method('getLocalProps')->willReturn(['prop1' => 'localValue', 'prop2' => 'localValue']);
        $addedRecord->method('getForeignProps')->willReturn(['prop1' => 'foreignValue']);
        $addedRecord->method('getClassification')->willReturn('tx_in2publishcore_domain_model_test');
        $addedRecord->method('getState')->willReturn('');

        $databaseRecordPublisher->publish($addedRecord);
    }

    /**
     * @covers ::finish
     */
    public function testFinishCommitsDatabaseChanges()
    {
        $databaseRecordPublisher = new DatabaseRecordPublisher();
        $foreignDatabase =  $this->createMock(Connection::class);
        $foreignDatabase->method('isTransactionActive')->willReturn(true);

        $foreignDatabase->expects($this->once())->method('commit');
        $databaseRecordPublisher->injectForeignDatabase($foreignDatabase);

        $databaseRecordPublisher->finish();
    }

    /**
     * @covers ::cancel
     */
    public function testCancelRollsBackDatabaseChanges()
    {
        $databaseRecordPublisher = new DatabaseRecordPublisher();
        $foreignDatabase =  $this->createMock(Connection::class);
        $foreignDatabase->method('isTransactionActive')->willReturn(true);

        $foreignDatabase->expects($this->once())->method('rollBack');
        $databaseRecordPublisher->injectForeignDatabase($foreignDatabase);

        $databaseRecordPublisher->cancel();
    }
}
