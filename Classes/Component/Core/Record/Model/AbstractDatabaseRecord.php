<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

use In2code\In2publishCore\Service\Configuration\TcaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractDatabaseRecord extends AbstractRecord
{
    // Defaults for values if the given CTRL key does not exist in the TCA ctrl section
    protected const CTRL_DEFAULT = [
        'languageField' => 0,
        'transOrigPointerField' => 0,
        'delete' => false,
    ];
    protected string $table;

    public function getClassification(): string
    {
        return $this->table;
    }

    public function getForeignIdentificationProps(): array
    {
        return [
            'uid' => $this->getId(),
        ];
    }

    public function getLanguage(): int
    {
        return $this->getCtrlProp('languageField');
    }

    public function getTransOrigPointer(): int
    {
        return $this->getCtrlProp('transOrigPointerField');
    }

    protected function getCtrlProp(string $ctrlName)
    {
        $value = self::CTRL_DEFAULT[$ctrlName] ?? null;

        $valueField = $GLOBALS['TCA'][$this->table]['ctrl'][$ctrlName] ?? null;

        if (null !== $valueField) {
            $value = $this->getProp($valueField) ?? $value;
        }

        return $value;
    }

    public function getDependencies(): array
    {
        $dependencies = [];
        if (null !== $this->translationParent && Record::S_ADDED === $this->translationParent->getState()) {
            $dependencies[] = new Dependency(
                $this->getClassification(),
                $this->getId(),
                $this->translationParent->getClassification(),
                $this->translationParent->getId(),
                'A translation parent must be published for a translated record to be visible.'
            );
        }
        if ('pages' !== $this->table) {
            $pid = $this->getProp('pid');
            if (0 !== $pid) {
                $hasParent = false;
                foreach ($this->parents as $parent) {
                    if ('pages' === $parent->getClassification() && $pid === $parent->getId()) {
                        $hasParent = true;
                        break;
                    }
                }
                if (false === $hasParent && null !== $pid) {
                    $dependencies[] = new Dependency(
                        $this->getClassification(),
                        $this->getId(),
                        'pages',
                        $pid,
                        'Records only exist on pages, therefore the page must exist.'
                    );
                }
            }
        }
        return $dependencies;
    }

    public function __toString(): string
    {
        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        return $tcaService->getRecordLabel($this->localProps, $this->table);
    }
}
