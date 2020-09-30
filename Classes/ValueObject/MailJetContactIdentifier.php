<?php

declare(strict_types=1);

namespace t3n\MailJetAdapter\ValueObject;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class MailJetContactIdentifier implements \JsonSerializable
{
    /**
     * @var int
     */
    private $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('The identifier must be a positive integer', 1601457022213);
        }
        $this->value = $value;
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
