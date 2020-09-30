<?php

declare(strict_types=1);

namespace t3n\MailJetAdapter\ValueObject;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Validation\Validator\EmailAddressValidator;

/**
 * @Flow\Proxy(false)
 */
final class MailJetContactEmail implements \JsonSerializable
{
    /**
     * @var string
     */
    private $value;

    public function __construct(string $value)
    {
        $validator = new EmailAddressValidator();

        if (strlen($value) !== '' && ! $validator->validate($value)->hasErrors()) {
            $this->value = $value;
        } else {
            throw new \InvalidArgumentException('The identifier must be a valid E-Mail!', 1601454557743);
        }
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
