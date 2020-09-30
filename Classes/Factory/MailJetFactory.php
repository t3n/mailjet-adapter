<?php

declare(strict_types=1);

namespace t3n\MailJetAdapter\Factory;

use Mailjet\Client;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class MailJetFactory
{
    /**
     * @Flow\InjectConfiguration(path="mailjet.clientConfiguration")
     *
     * @var string[]
     */
    protected $clientConfiguration;

    /**
     * @var Client|null
     */
    protected $client = null;

    public function createClient(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $client = new Client($this->clientConfiguration['apiKey'], $this->clientConfiguration['apiSecret'], (bool) $this->clientConfiguration['liveMode'], ['version' => $this->clientConfiguration['apiVersion']]);
        $this->client = $client;

        return $client;
    }
}
