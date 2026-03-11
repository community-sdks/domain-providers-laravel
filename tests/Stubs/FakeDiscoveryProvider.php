<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Tests\Stubs;

use DomainProviders\Contract\DomainProviderInterface;
use DomainProviders\Contract\TldDiscoveryInterface;
use DomainProviders\DTO\AvailabilityResult;
use DomainProviders\DTO\DnsRecord;
use DomainProviders\DTO\DomainContact;
use DomainProviders\DTO\DomainInfo;
use DomainProviders\DTO\DomainName;
use DomainProviders\DTO\DomainPrice;
use DomainProviders\DTO\DomainRegistrationPeriod;
use DomainProviders\DTO\NameserverSet;
use DomainProviders\DTO\OperationResult;
use DomainProviders\DTO\ProviderMetadata;
use DomainProviders\DTO\TransferAvailabilityResult;

final class FakeDiscoveryProvider implements DomainProviderInterface, TldDiscoveryInterface
{
    /** @var list<string> */
    private array $extensions;

    /** @param list<string> $extensions */
    public function __construct(array $extensions = ['com', 'net'])
    {
        $this->extensions = $extensions;
    }

    public function listSupportedTlds(): array
    {
        return $this->extensions;
    }

    public function metadata(): ProviderMetadata
    {
        return new ProviderMetadata('Fake', 'fake', 'test', null, $this->extensions, []);
    }

    public function supports(string $capability): bool
    {
        return true;
    }

    public function checkAvailability(DomainName $domain): AvailabilityResult
    {
        throw new \RuntimeException('Not used in tests.');
    }

    public function registerDomain(DomainName $domain, DomainRegistrationPeriod $period, DomainContact $registrantContact, ?NameserverSet $nameservers = null, ?bool $privacyEnabled = null, ?string $marketId = null): OperationResult
    {
        throw new \RuntimeException('Not used in tests.');
    }

    public function renewDomain(DomainName $domain, DomainRegistrationPeriod $period): OperationResult
    {
        throw new \RuntimeException('Not used in tests.');
    }

    public function transferDomain(DomainName $domain, string $authCode, ?DomainContact $registrantContact = null): OperationResult
    {
        throw new \RuntimeException('Not used in tests.');
    }

    public function getDomainInfo(DomainName $domain): DomainInfo
    {
        throw new \RuntimeException('Not used in tests.');
    }

    public function listDomains(?int $page = null, ?int $pageSize = null, ?string $status = null, ?string $shopperId = null): array
    {
        throw new \RuntimeException('Not used in tests.');
    }

    public function getNameservers(DomainName $domain): NameserverSet
    {
        throw new \RuntimeException('Not used in tests.');
    }

    public function setNameservers(DomainName $domain, NameserverSet $nameservers): OperationResult
    {
        throw new \RuntimeException('Not used in tests.');
    }

    public function listDnsRecords(DomainName $domain): array
    {
        throw new \RuntimeException('Not used in tests.');
    }

    public function createDnsRecord(DomainName $domain, DnsRecord $record, ?string $shopperId = null): OperationResult
    {
        throw new \RuntimeException('Not used in tests.');
    }

    public function updateDnsRecord(DomainName $domain, DnsRecord $record, ?string $shopperId = null): OperationResult
    {
        throw new \RuntimeException('Not used in tests.');
    }

    public function deleteDnsRecord(DomainName $domain, ?string $recordId = null, ?DnsRecord $matchRecord = null, ?string $shopperId = null): OperationResult
    {
        throw new \RuntimeException('Not used in tests.');
    }

    public function getDomainPricing(?DomainName $domain = null, ?string $tld = null, ?DomainRegistrationPeriod $period = null): DomainPrice
    {
        throw new \RuntimeException('Not used in tests.');
    }

    public function checkTransferAvailability(DomainName $domain): TransferAvailabilityResult
    {
        throw new \RuntimeException('Not used in tests.');
    }
}
