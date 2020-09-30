<?php

declare(strict_types=1);

namespace t3n\MailJetAdapter\Model;

use Doctrine\Common\Collections\ArrayCollection;
use t3n\MailJetAdapter\ValueObject\MailJetContactEmail;
use t3n\MailJetAdapter\ValueObject\MailJetContactIdentifier;

class Contact
{
    /**
     * @var MailJetContactIdentifier
     */
    protected $identifier;

    /**
     * @var MailJetContactEmail
     */
    protected $email;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $deliveredCount;

    /**
     * @var bool;
     */
    protected $isOptInPending;

    /**
     * @var \DateTime|null
     */
    protected $lastActivityAt;

    /**
     * @var \DateTime|null
     */
    protected $lastUpdateAt;

    /**
     * @var bool
     */
    protected $isSpamComplaining;

    /**
     * @var \DateTime|null
     */
    protected $exclusionFromCampaignsUpdatedAt;

    /**
     * @var bool
     */
    protected $isExcludedFromCampaigns;

    /**
     * @var ArrayCollection<ContactData>
     */
    protected $contactData;

    public function __construct(MailJetContactIdentifier $identifier)
    {
        $this->identifier = $identifier;
        $this->contactData = new ArrayCollection();
    }

    public function getIdentifier(): MailJetContactIdentifier
    {
        return $this->identifier;
    }

    public function getEmail(): MailJetContactEmail
    {
        return $this->email;
    }

    public function setEmail(MailJetContactEmail $email): void
    {
        $this->email = $email;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDeliveredCount(): int
    {
        return $this->deliveredCount;
    }

    public function setDeliveredCount(int $deliveredCount): void
    {
        $this->deliveredCount = $deliveredCount;
    }

    public function isOptInPending(): bool
    {
        return $this->isOptInPending;
    }

    public function setIsOptInPending(bool $isOptInPending): void
    {
        $this->isOptInPending = $isOptInPending;
    }

    public function getLastActivityAt(): ?\DateTime
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTime $lastActivityAt): void
    {
        $this->lastActivityAt = $lastActivityAt;
    }

    public function getLastUpdateAt(): ?\DateTime
    {
        return $this->lastUpdateAt;
    }

    public function setLastUpdateAt(?\DateTime $lastUpdateAt): void
    {
        $this->lastUpdateAt = $lastUpdateAt;
    }

    public function isSpamComplaining(): bool
    {
        return $this->isSpamComplaining;
    }

    public function setIsSpamComplaining(bool $isSpamComplaining): void
    {
        $this->isSpamComplaining = $isSpamComplaining;
    }

    public function getExclusionFromCampaignsUpdatedAt(): ?\DateTime
    {
        return $this->exclusionFromCampaignsUpdatedAt;
    }

    public function setExclusionFromCampaignsUpdatedAt(?\DateTime $exclusionFromCampaignsUpdatedAt): void
    {
        $this->exclusionFromCampaignsUpdatedAt = $exclusionFromCampaignsUpdatedAt;
    }

    public function isExcludedFromCampaigns(): bool
    {
        return $this->isExcludedFromCampaigns;
    }

    public function setIsExcludedFromCampaigns(bool $isExcludedFromCampaigns): void
    {
        $this->isExcludedFromCampaigns = $isExcludedFromCampaigns;
    }

    public function getContactData(): ArrayCollection
    {
        return $this->contactData;
    }

    public function setContactData(ArrayCollection $contactData): void
    {
        $this->contactData = $contactData;
    }
}
