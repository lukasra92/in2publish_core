<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer;

/*
 * Copyright notice
 *
 * (c) 2018 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\ConfigContainer\Definer\DefinerInterface;
use In2code\In2publishCore\Component\ConfigContainer\Migration\MigrationInterface;
use In2code\In2publishCore\Component\ConfigContainer\Node\Node;
use In2code\In2publishCore\Component\ConfigContainer\Node\NodeCollection;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\PostProcessorInterface;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ContextualProvider;
use In2code\In2publishCore\Component\ConfigContainer\Provider\ProviderInterface;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Utility\ArrayUtility;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_combine;
use function array_fill;
use function array_keys;
use function array_merge;
use function asort;
use function count;

class ConfigContainer implements SingletonInterface
{
    protected ContextService $contextService;
    protected array $providers = [];
    /** @var array<class-string<DefinerInterface>, DefinerInterface|null> */
    protected array $definers = [];
    /** @var array<class-string<PostProcessorInterface>, PostProcessorInterface|null> */
    protected array $postProcessors = [];
    /** @var array<class-string<MigrationInterface>, MigrationInterface|null> */
    protected array $migrations = [];
    protected ?array $config = null;
    /** @var NodeCollection[]|null[] */
    protected array $definition = [
        'local' => null,
        'foreign' => null,
    ];

    public function __construct(ContextService $contextService)
    {
        $this->contextService = $contextService;
    }

    /** @return mixed */
    public function get(string $path = '')
    {
        $config = $this->getConfig();
        if (!empty($path)) {
            return ArrayUtility::getValueByPath($config, $path);
        }
        return $config;
    }

    protected function getConfig(): array
    {
        if (null !== $this->config) {
            return $this->config;
        }
        $complete = true;
        $priority = [];
        foreach ($this->providers as $class => $config) {
            $provider = GeneralUtility::makeInstance($class);
            if ($provider instanceof ProviderInterface) {
                if (null === $config) {
                    if ($provider->isAvailable()) {
                        $this->providers[$class] = $provider->getConfig();
                        $priority[$class] = $provider->getPriority();
                    } else {
                        $complete = false;
                    }
                } else {
                    $priority[$class] = $provider->getPriority();
                }
            }
        }

        asort($priority);

        $config = $this->processConfig($priority);

        if (true === $complete) {
            $this->config = $config;
        }

        return $config;
    }

    /**
     * Returns the configuration without any contextual parts.
     * Is always "fresh" but never guaranteed to be complete.
     */
    public function getContextFreeConfig(): array
    {
        $priority = [];
        foreach ($this->providers as $class => $config) {
            $provider = GeneralUtility::makeInstance($class);
            if ($provider instanceof ProviderInterface && !($provider instanceof ContextualProvider)) {
                if (null === $config) {
                    if ($provider->isAvailable()) {
                        $this->providers[$class] = $provider->getConfig();
                        $priority[$class] = $provider->getPriority();
                    }
                } else {
                    $priority[$class] = $provider->getPriority();
                }
            }
        }

        return $this->processConfig($priority);
    }

    /**
     * Applies the configuration of each provider in order of priority.
     *
     * @param array $priority
     *
     * @return array|array[]|bool[]|int[]|string[] Sorted, merged and type cast configuration.
     */
    protected function processConfig(array $priority): array
    {
        asort($priority);

        $config = [];
        foreach (array_keys($priority) as $class) {
            $providerConfig = $this->providers[$class];
            $config = ConfigurationUtility::mergeConfiguration($config, $providerConfig);
        }

        foreach ($this->postProcessors as $class => $object) {
            if (null === $object) {
                $object = GeneralUtility::makeInstance($class);
                $this->postProcessors[$class] = $object;
            }
            if ($object instanceof PostProcessorInterface) {
                $config = $object->process($config);
            }
        }

        $config = $this->migrateConfig($config);

        if ($this->contextService->isLocal()) {
            $config = $this->getLocalDefinition()->cast($config);
        } else {
            $config = $this->getForeignDefinition()->cast($config);
        }

        return $config;
    }

    protected function migrateConfig(array $config): array
    {
        foreach ($this->migrations as $class => $migration) {
            if (null === $migration) {
                $this->migrations[$class] = $migration = GeneralUtility::makeInstance($class);
            }

            $config = $migration->migrate($config);
        }
        return $config;
    }

    public function getLocalDefinition(string $path = ''): Node
    {
        if (null === $this->definition['local']) {
            $definition = GeneralUtility::makeInstance(NodeCollection::class);
            foreach ($this->definers as $class => $definer) {
                if ($definer === null) {
                    $this->definers[$class] = $definer = GeneralUtility::makeInstance($class);
                }
                if ($definer instanceof DefinerInterface) {
                    $definition->addNodes($definer->getLocalDefinition());
                }
            }
            $this->definition['local'] = $definition;
        }
        return $this->definition['local']->getNodePath($path);
    }

    public function getForeignDefinition(string $path = ''): Node
    {
        if (null === $this->definition['foreign']) {
            $definition = GeneralUtility::makeInstance(NodeCollection::class);
            foreach ($this->definers as $class => $definer) {
                if ($definer === null) {
                    $this->definers[$class] = $definer = GeneralUtility::makeInstance($class);
                }
                if ($definer instanceof DefinerInterface) {
                    $definition->addNodes($definer->getForeignDefinition());
                }
            }
            $this->definition['foreign'] = $definition;
        }
        return $this->definition['foreign']->getNodePath($path);
    }

    /**
     * All providers must be registered in ext_localconf.php!
     * Providers registered in ext_tables.php will not overrule configurations of already loaded extensions.
     * Providers must implement the ProviderInterface, or they won't be called.
     */
    public function registerProvider(string $provider): void
    {
        $this->providers[$provider] = null;
    }

    /**
     * All definers must be registered in ext_localconf.php!
     * Definers must implement the DefinerInterface, or they won't be called.
     */
    public function registerDefiner(string $definer): void
    {
        $this->definers[$definer] = null;
    }

    /**
     * All post processors must be registered in ext_localconf.php!
     * PostProcessors must implement the PostProcessorInterface, or they won't be called.
     */
    public function registerPostProcessor(string $postProcessor): void
    {
        $this->postProcessors[$postProcessor] = null;
    }

    /**
     * All migrations must be registered in ext_localconf.php!
     * Migrations must implement the MigrationInterface.
     */
    public function registerMigration(string $migration): void
    {
        $this->migrations[$migration] = null;
    }

    public function getMigrationMessages(): array
    {
        $messages = [];
        foreach ($this->migrations as $class => $migration) {
            if (null === $migration) {
                $this->migrations[$class] = $migration = GeneralUtility::makeInstance($class);
            }
            $messages[] = $migration->getMessages();
        }
        return array_merge([], ...$messages);
    }

    /**
     * Returns the information about all registered classes which are responsible for the resulting configuration.
     */
    public function dump(): array
    {
        // Clone this instance and reset it
        $cloned = clone $this;
        $cloned->config = null;
        $cloned->providers = array_combine(array_keys($this->providers), array_fill(0, count($this->providers), null));
        $cloned->definers = array_combine(array_keys($this->definers), array_fill(0, count($this->definers), null));
        $cloned->postProcessors = array_combine(
            array_keys($this->postProcessors),
            array_fill(0, count($this->postProcessors), null)
        );
        $fullConfig = $cloned->get();

        $priority = [];
        foreach (array_keys($cloned->providers) as $class) {
            $provider = GeneralUtility::makeInstance($class);
            if ($provider instanceof ProviderInterface) {
                $priority[$class] = $provider->getPriority();
            }
        }

        asort($priority);

        $orderedProviderConfig = [];
        foreach (array_keys($priority) as $class) {
            $orderedProviderConfig[$class] = $cloned->providers[$class];
        }

        return [
            'fullConfig' => $fullConfig,
            'providers' => $orderedProviderConfig,
            'definers' => array_keys($cloned->definers),
            'postProcessors' => array_keys($cloned->postProcessors),
            'migrations' => $cloned->migrations,
        ];
    }
}
