<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

class Dependency
{
    private string $fromClassifier;
    private $fromId;
    private string $toClassifier;
    private $toId;
    private string $reason;

    public function __construct(string $fromClassifier, $fromId, string $toClassifier, $toId, string $reason)
    {
        $this->fromClassifier = $fromClassifier;
        $this->fromId = $fromId;
        $this->toClassifier = $toClassifier;
        $this->toId = $toId;
        $this->reason = $reason;
    }

    public function getFromClassifier(): string
    {
        return $this->fromClassifier;
    }

    public function getFromId()
    {
        return $this->fromId;
    }

    public function getToClassifier(): string
    {
        return $this->toClassifier;
    }

    public function getToId()
    {
        return $this->toId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function __toString(): string
    {
        return "The record $this->fromClassifier [$this->fromId] has a dependency to $this->toClassifier [$this->toId] because $this->reason";
    }
}
