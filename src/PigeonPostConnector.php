<?php

declare(strict_types=1);

namespace Sifrious\PigeonPost;

use DateTimeImmutable;
use Sifrious\Aleph\Connector\ConfigurationField;
use Sifrious\Aleph\Connector\ConfigurationSchema;
use Sifrious\Aleph\Connector\Connector;
use Sifrious\Aleph\Connector\Contracts\Backfills;
use Sifrious\Aleph\Connector\Contracts\DiscoversSources;
use Sifrious\Aleph\Connector\Values\DiscoveredSource;
use Sifrious\Aleph\Connector\Values\DiscoveredSources;
use Sifrious\Aleph\Connector\Values\OperationRequest;
use Sifrious\Aleph\Connector\Values\OperationResult;
use Sifrious\Aleph\Envelope\EnvelopeSubmitter;
use Sifrious\Aleph\Envelope\ExtensionMetadata;
use Sifrious\Aleph\Envelope\ObservationEnvelope;
use Sifrious\Aleph\Envelope\Provenance;

final readonly class PigeonPostConnector implements Backfills, Connector, DiscoversSources
{
    public const EXTENSION_NAMESPACE = 'pigeonpost.dispatch';

    public const EXTENSION_VERSION = 2;

    private const LOFTS = ['ashcroft', 'brindle'];

    private const DISPATCHES = [
        'ashcroft' => [
            ['ring' => 'RN-1041', 'message' => 'wheat barge sighted', 'wind' => 'NNE 12kt', 'weight_g' => 4],
            ['ring' => 'RN-1042', 'message' => 'bridge is out at millford', 'wind' => 'NNE 9kt', 'weight_g' => 3],
        ],
        'brindle' => [
            ['ring' => 'RN-2207', 'message' => 'hawk over the north field', 'wind' => 'SW 21kt', 'weight_g' => 5],
        ],
    ];

    public function __construct(
        private EnvelopeSubmitter $submitter,
        private LoftCursors $cursors,
    ) {}

    public function id(): string
    {
        return 'pigeon-post';
    }

    public function name(): string
    {
        return 'Pigeon Post';
    }

    public function version(): string
    {
        return '0.3.1';
    }

    public function configuration(): ConfigurationSchema
    {
        return new ConfigurationSchema(
            ConfigurationField::text('loft_registry_url', 'Where the loft registry is published'),
            ConfigurationField::secret('keeper_passphrase', 'Passphrase for the keeper ledger'),
            ConfigurationField::boolean('include_weather', 'Attach wind readings to each dispatch'),
        );
    }

    public function discoverSources(OperationRequest $request): DiscoveredSources
    {
        return new DiscoveredSources(...array_map(
            static fn (string $loft): DiscoveredSource => new DiscoveredSource(
                'pigeon-post:loft/'.$loft,
                ucfirst($loft).' Loft',
                ['dispatch_count' => count(self::DISPATCHES[$loft])],
            ),
            self::LOFTS,
        ));
    }

    public function backfill(OperationRequest $request): OperationResult
    {
        $loft = (string) $request->parameter('loft', 'ashcroft');
        $installation = (string) $request->parameter('installation', 'unknown');

        if (! isset(self::DISPATCHES[$loft])) {
            return OperationResult::failed("Unknown loft [{$loft}].");
        }

        $accepted = 0;
        $lastRing = null;

        foreach (self::DISPATCHES[$loft] as $dispatch) {
            $this->submitter->submit($this->envelopeFor($loft, $installation, $dispatch));
            $accepted++;
            $lastRing = $dispatch['ring'];
        }

        if ($lastRing !== null) {
            $this->cursors->remember($installation, $loft, $lastRing, $accepted);
        }

        return OperationResult::completed($accepted, ['loft' => $loft, 'last_ring' => $lastRing]);
    }

    /**
     * @param  array{ring: string, message: string, wind: string, weight_g: int}  $dispatch
     */
    private function envelopeFor(string $loft, string $installation, array $dispatch): ObservationEnvelope
    {
        return new ObservationEnvelope(
            sourceReference: 'pigeon-post:loft/'.$loft,
            sourceName: ucfirst($loft).' Loft',
            resourceReference: 'pigeon-post:dispatch/'.$dispatch['ring'],
            observedAt: new DateTimeImmutable('2026-08-27T09:00:00+00:00'),
            payload: $dispatch['message'],
            provenance: new Provenance(
                connectorId: $this->id(),
                connectorVersion: $this->version(),
                installationId: $installation,
                capturedAt: new DateTimeImmutable('2026-08-27T09:05:00+00:00'),
            ),
            contentType: 'text/plain',
            account: $installation,
            stream: 'loft/'.$loft,
            eventType: 'pigeon.dispatch.arrived',
            providerId: $dispatch['ring'],
            providerRevision: '1',
            extensions: [
                new ExtensionMetadata(self::EXTENSION_NAMESPACE, self::EXTENSION_VERSION, [
                    'ring_number' => $dispatch['ring'],
                    'wind' => $dispatch['wind'],
                    'weight_g' => $dispatch['weight_g'],
                    'loft' => $loft,
                ]),
            ],
        );
    }
}
