<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Publisher;

use Exception;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Communication\TemporaryAssetTransmission\AssetTransmitter;
use In2code\In2publishCore\Component\TcaHandling\FileHandling\Service\FalDriverService;
use In2code\In2publishCore\Domain\Model\FileRecord;
use In2code\In2publishCore\Domain\Model\Record;
use TYPO3\CMS\Core\Database\Connection;

use function bin2hex;
use function random_bytes;
use function unlink;

class FileRecordPublisher implements Publisher, FinishablePublisher
{
    // All A_* constant values must be 6 chars
    public const A_DELETE = 'delete';
    public const A_INSERT = 'insert';
    public const A_UPDATE = 'update';
    protected AssetTransmitter $assetTransmitter;
    protected FalDriverService $falDriverService;
    protected Connection $foreignDatabase;
    protected RemoteCommandDispatcher $remoteCommandDispatcher;
    protected string $requestToken;
    protected bool $hasTasks = false;

    public function __construct()
    {
        $this->requestToken = bin2hex(random_bytes(16));
    }

    public function injectAssetTransmitter(AssetTransmitter $assetTransmitter): void
    {
        $this->assetTransmitter = $assetTransmitter;
    }

    public function injectFalDriverService(FalDriverService $falDriverService): void
    {
        $this->falDriverService = $falDriverService;
    }

    public function injectForeignDatabase(Connection $foreignDatabase): void
    {
        $this->foreignDatabase = clone $foreignDatabase;
        $this->foreignDatabase->close();
        $this->foreignDatabase->connect();
    }

    public function injectRemoteCommandDispatcher(RemoteCommandDispatcher $remoteCommandDispatcher): void
    {
        $this->remoteCommandDispatcher = $remoteCommandDispatcher;
    }

    public function canPublish(Record $record): bool
    {
        return $record instanceof FileRecord;
    }

    public function publish(Record $record): void
    {
        if ($record->getState() === Record::S_DELETED) {
            $this->hasTasks = true;
            $this->foreignDatabase->insert('tx_in2publishcore_filepublisher_task', [
                'request_token' => $this->requestToken,
                'crdate' => $GLOBALS['EXEC_TIME'],
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'storage_uid' => $record->getForeignProps()['storage'],
                'identifier' => $record->getForeignProps()['identifier'],
                'identifier_hash' => $record->getForeignProps()['identifier_hash'],
                'file_action' => self::A_DELETE,
                'temp_identifier_hash' => null,
            ]);
            return;
        }
        if ($record->getState() === Record::S_ADDED) {
            $this->hasTasks = true;
            $this->transmitFile($record, self::A_INSERT);
            return;
        }
        if ($record->getState() === Record::S_CHANGED) {
            $this->hasTasks = true;
            $this->transmitFile($record, self::A_UPDATE);
        }
    }

    protected function transmitFile(Record $record, string $action): void
    {
        $driver = $this->falDriverService->getDriver($record->getLocalProps()['storage']);
        $localFile = $driver->getFileForLocalProcessing($record->getLocalProps()['identifier']);
        $identifier = $this->assetTransmitter->transmitTemporaryFile($localFile);
        unlink($localFile);
        $this->foreignDatabase->insert('tx_in2publishcore_filepublisher_task', [
            'request_token' => $this->requestToken,
            'crdate' => $GLOBALS['EXEC_TIME'],
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'storage_uid' => $record->getLocalProps()['storage'],
            'identifier' => $record->getLocalProps()['identifier'],
            'identifier_hash' => $record->getLocalProps()['identifier_hash'],
            'file_action' => $action,
            'temp_identifier_hash' => $identifier,
        ]);
    }

    public function finish(): void
    {
        if ($this->hasTasks) {
            $request = new RemoteCommandRequest('in2publish_core:tcahandling:filepublisher', [], [$this->requestToken]);
            $response = $this->remoteCommandDispatcher->dispatch($request);
            if (!$response->isSuccessful()) {
                throw new Exception($response->getErrorsString() . $response->getOutputString());
            }
        }
    }
}