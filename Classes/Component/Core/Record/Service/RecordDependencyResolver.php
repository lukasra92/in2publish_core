<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Service;

use In2code\In2publishCore\Component\Core\Record\Model\Dependency;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Component\Core\RecordTree\Traverser\RecordTreeTraverser;

class RecordDependencyResolver
{
    /**
     * @return array<Dependency>
     */
    public function resolve(RecordTree $recordTree): array
    {
        $pageRecords = $this->extractPageRecords($recordTree);
        foreach ($pageRecords as $pageRecord) {
            $deps = $pageRecord->getInheritedDependencies();
        }

        return [];
    }

    /**
     * @return array<Record>
     */
    public function extractPageRecords(RecordTree $recordTree): array
    {
        $traverser = new RecordTreeTraverser();

        $pageRecords = [];
        $traverser->addVisitor(static function (string $event, Record $record) use (&$pageRecords) {
            if (RecordTreeTraverser::EVENT_ENTER !== $event) {
                return;
            }
            if ('pages' === $record->getClassification()) {
                $pageRecords[] = $record;
            }
        });

        $traverser->run($recordTree);
        return $pageRecords;
    }

    protected function getDependencies(RecordTree $recordTree): array
    {
        $traverser = new RecordTreeTraverser();

        $isRoot = true;
        $dependencies = [];
        $traverser->addVisitor(static function (string $event, Record $record) use (&$dependencies, &$isRoot): ?string {
            if (!$isRoot && 'pages' === $record->getClassification()) {
                return RecordTreeTraverser::OP_IGNORE;
            }
            $isRoot = false;
            if (RecordTreeTraverser::EVENT_ENTER !== $event) {
                return null;
            }
            foreach ($record->getInheritedDependencies() as $dependency) {
                $dependencies[] = $dependency;
            }
            return null;
        });

        $traverser->run($recordTree);
        return $dependencies;
    }
}
