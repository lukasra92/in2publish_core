<?php

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\Publisher;

use In2code\In2publishCore\Communication\TemporaryAssetTransmission\AssetTransmitter;
use In2code\In2publishCore\Component\TcaHandling\FileHandling\Service\FalDriverService;
use In2code\In2publishCore\Component\TcaHandling\Publisher\FileRecordPublisher;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Model\FileRecord;
use In2code\In2publishCore\Tests\UnitTestCase;
use TYPO3\CMS\Core\Database\Connection;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\TcaHandling\Publisher\FileRecordPublisher
 */
class FileRecordPublisherTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     * @covers ::canPublish
     */
    public function testCanPublishReturnsTrueForFileRecordsOnly()
    {
        $fileRecordPublisher = new FileRecordPublisher();

        $fileRecord = $this->createMock(FileRecord::class);
        $this->assertTrue($fileRecordPublisher->canPublish($fileRecord));

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $this->assertFalse($fileRecordPublisher->canPublish($databaseRecord));
    }

    /**
     * @covers ::publish
     */
    public function testPublishDeletesRemovedFile()
    {
        $fileRecordPublisher = new FileRecordPublisher();
        $foreignDatabase =  $this->createMock(Connection::class);

        $deletedFile = $this->createMock(FileRecord::class);
        $deletedFile->method('getClassification')->willReturn('_file');
        $deletedFile->method('getState')->willReturn(\In2code\In2publishCore\Domain\Model\Record::S_DELETED);
        $deletedFile->method('getForeignProps')->willReturn(
            ['storage' => 1, 'identifier' => 'bar', 'identifier_hash' => 'baz']
        );

        $reflectionProperty = new \ReflectionProperty($fileRecordPublisher, 'requestToken');
        $reflectionProperty->setAccessible(true);

        $foreignDatabase->expects($this->once())->method('insert')->with(
            'tx_in2publishcore_filepublisher_task',
            [
                'request_token' => $reflectionProperty->getValue($fileRecordPublisher),
                'crdate' => $GLOBALS['EXEC_TIME'],
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'storage_uid' => $deletedFile->getForeignProps()['storage'],
                'identifier' => $deletedFile->getForeignProps()['identifier'],
                'identifier_hash' => $deletedFile->getForeignProps()['identifier_hash'],
                'file_action' => $fileRecordPublisher::A_DELETE,
            ]
        );
        $fileRecordPublisher->injectForeignDatabase($foreignDatabase);

        $fileRecordPublisher->publish($deletedFile);
    }
}
