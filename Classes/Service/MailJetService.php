<?php

declare(strict_types=1);

namespace t3n\MailJetAdapter\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Mailjet\Client;
use Mailjet\Resources;
use Mailjet\Response;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\DateTimeConverter;
use Neos\Flow\Reflection\ReflectionService;
use t3n\MailJetAdapter\Exception\MailJetInvalidContactDataException;
use t3n\MailJetAdapter\Exception\MailJetRequestException;
use t3n\MailJetAdapter\Model\Contact;
use t3n\MailJetAdapter\Model\ContactData;
use t3n\MailJetAdapter\ValueObject\MailJetContactEmail;
use t3n\MailJetAdapter\ValueObject\MailJetContactIdentifier;

/**
 * @Flow\Scope("singleton")
 */
class MailJetService
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @Flow\Inject
     *
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     *
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @var mixed[]
     */
    protected $runtimeCache = ['contactData' => null];

    protected $allowedContactMetaDataTypes = ['str', 'datetime', 'int', 'float', 'bool'];

    public function getContactByEmail(MailJetContactEmail $email, bool $includeAttributes): ?Contact
    {
        return $this->fetchContact((string) $email, $includeAttributes);
    }

    public function getContactByIdentifier(MailJetContactIdentifier $identifier, bool $includeAttributes = true): ?Contact
    {
        return $this->fetchContact((string) $identifier, $includeAttributes);
    }

    public function addNewContact(MailJetContactEmail $email, string $name): Contact
    {
        $response = $this->client->post(Resources::$Contact, ['body' => ['Name' => $name, 'Email' => (string) $email]]);
        $result = $this->parseResult($response);

        return $this->createContactFromRawData($result['data'][0]);
    }

    public function updateContactName(MailJetContactIdentifier $identifier, string $name): Contact
    {
        $response = $this->client->put(Resources::$Contact, ['id' => (string) $identifier, 'body' => ['Name' => $name]]);
        $result = $this->parseResult($response);

        return $this->createContactFromRawData($result['data'][0]);
    }

    public function syncContactDataAttributes(Contact $contact): bool
    {
        $mappedData = [];
        $contact->getContactData()->map(static function (ContactData $data) use (&$mappedData): void {
            if (is_bool($data->getValue())) {
                $value = $data->getValue() === true ? 'true' : 'false';
            } elseif ($data->getValue() instanceof \DateTimeInterface) {
                $value = $data->getValue()->format(DATE_ATOM);
            } else {
                $value = (string) $data->getValue();
            }
            $mappedData[] = ['Name' => $data->getName(), 'Value' => $value];
        });

        $response = $this->client->put(Resources::$Contactdata, ['id' => (string) $contact->getIdentifier(), 'body' => ['Data' => $mappedData]]);
        $result = $this->parseResult($response);

        return $result['count'] === 1;
    }

    public function addNewContactMetadataField(string $name, string $type, string $nameSpace = 'static'): bool
    {
        $exist = $this->getContactMetaProperty($name);

        if ($exist !== null) {
            throw new MailJetInvalidContactDataException(sprintf('A Metadata field with the name %s already exist', $name));
        }

        $response = $this->client->post(Resources::$Contactmetadata, ['body' => ['Name' => $name, 'NameSpace' => $nameSpace, 'Datatype' => $type]]);
        $result = $this->parseResult($response);

        return $result['count'] === 1;
    }

    /**
     * @return mixed[]
     */
    public function getContactMetaData(): array
    {
        if (empty($this->runtimeCache['contactData'])) {
            $responseMeta = $this->client->get(Resources::$Contactmetadata, ['filters' => ['Limit' => 1000]]);
            $this->runtimeCache['contactData'] = $this->parseResult($responseMeta)['data'];
        }

        return $this->runtimeCache['contactData'];
    }

    protected function fetchContact(string $identifier, bool $includeAttributes): ?Contact
    {
        $response = $this->client->get(Resources::$Contact, ['id' => $identifier]);
        try {
            $result = $this->parseResult($response);
        } catch (MailJetRequestException $e) {
            return null;
        }

        if ($result['count'] === 0) {
            return null;
        }

        $contact = $this->createContactFromRawData($result['data'][0]);

        if ($includeAttributes) {
            $attributes = $this->fetchContactData($contact->getIdentifier());
            $contact->setContactData($attributes);
        }

        return $contact;
    }

    protected function getContactMetaProperty(string $name): ?array
    {
        $metaData = $this->getContactMetaData();

        $property = current(array_filter($metaData, static function ($el) use ($name) {
            return $name === $el['Name'];
        }));

        if ($property === false) {
            return null;
        }

        return $property;
    }

    protected function fetchContactData(MailJetContactIdentifier $identifier): ArrayCollection
    {
        // fetch contact data
        $response = $this->client->get(Resources::$Contactdata, ['id' => (string) $identifier]);
        $result = $this->parseResult($response);
        $rawData = $result['data'][0]['Data'];

        $contactData = new ArrayCollection();

        // Map raw contact data to the correct type
        foreach ($rawData as $d) {
            $metaData = $this->getContactMetaProperty($d['Name']);

            $value = new ContactData();
            $value->setName($d['Name']);

            /**
             * For possible data types
             *
             * @see https://dev.mailjet.com/email/reference/contacts/contact-properties/#v3_post_contactmetadata
             */
            switch ($metaData['Datatype']) {
                case 'datetime':
                    $value->setValue(new \DateTimeImmutable($d['Value']));
                    break;
                case 'int':
                    $value->setValue((int) $d['Value']);
                    break;
                case 'float':
                    $value->setValue((float) $d['Value']);
                    break;
                case 'bool':
                    $value->setValue($d['Value'] === 'true');
                    break;
                default:
                    $value->setValue($d['Value']);
                    break;
            }

            $contactData->add($value);
        }

        return $contactData;
    }

    /**
     * @return mixed[]
     */
    protected function parseResult(Response $response): array
    {
        if (! $response->success()) {
            throw new MailJetRequestException($response->getReasonPhrase(), 1601456074250);
        }

        return [
            'total' => $response->getTotal() ?? 0,
            'count' => $response->getCount() ?? 0,
            'data' => $response->getData()
        ];
    }

    /**
     * @param mixed[] $data
     */
    protected function createContactFromRawData(array $data): Contact
    {
        $properties = $this->reflectionService->getClassPropertyNames(Contact::class);

        $input = [];
        foreach ($data as $key => $value) {
            if ($key === 'ID') {
                $input['identifier'] = $value;
                continue;
            }

            // skip properties we don't want in our model
            if (! in_array(lcfirst($key), $properties)) {
                continue;
            }

            $input[lcfirst($key)] = $value;
        }

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->allowAllProperties();

        // Adjust mapping for DateTime values
        $propertyMappingConfiguration->forProperty('exclusionFromCampaignsUpdatedAt')->setTypeConverter(new DateTimeConverter());
        $propertyMappingConfiguration->forProperty('lastActivityAt')->setTypeConverter(new DateTimeConverter());
        $propertyMappingConfiguration->forProperty('lastUpdateAt')->setTypeConverter(new DateTimeConverter());

        return $this->propertyMapper->convert($input, Contact::class, $propertyMappingConfiguration);
    }
}
