<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter;

use RuntimeException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function in_array;

class TransmissionAdapterRegistry
{
    private array $adapters = [];
    private string $selectedAdapter;

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $this->selectedAdapter = $extensionConfiguration->get('in2publish_core', 'adapter/transmission');
    }

    public function registerAdapter(string $identifier, string $class, string $label, array $tests = []): bool
    {
        $this->adapters[$identifier] = [
            'class' => $class,
            'tests' => $tests,
            'label' => $label,
        ];

        if ($identifier === $this->selectedAdapter) {
            $this->addTests($tests, AdapterInterface::class);
        }

        return true;
    }

    protected function addTests(array $tests, string $interface): void
    {
        $GLOBALS['in2publish_core']['virtual_tests'][$interface] = $tests;
        foreach ($tests as $test) {
            if (
                empty($GLOBALS['in2publish_core']['tests'])
                || !in_array($test, $GLOBALS['in2publish_core']['tests'], true)
            ) {
                $GLOBALS['in2publish_core']['tests'][] = $test;
            }
        }
    }

    public function getAdapterRegistration(string $identifier): ?array
    {
        return $this->adapters[$identifier] ?? null;
    }

    public function getSelectedAdapter(): string
    {
        return $this->selectedAdapter;
    }

    public function createSelectedAdapter(): AdapterInterface
    {
        if (!array_key_exists($this->selectedAdapter, $this->adapters)) {
            throw new RuntimeException(
                "Could not create transmission adapter '$this->selectedAdapter': Adapter not found",
                1657115687
            );
        }
        return GeneralUtility::makeInstance($this->adapters[$this->selectedAdapter]['class']);
    }
}
