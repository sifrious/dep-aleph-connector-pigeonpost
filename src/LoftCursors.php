<?php

declare(strict_types=1);

namespace Sifrious\PigeonPost;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;

final readonly class LoftCursors
{
    public function __construct(private ConnectionInterface $connection) {}

    public function remember(string $installationId, string $loft, string $ringNumber, int $seen): void
    {
        $this->connection->table('pigeonpost_loft_cursors')->updateOrInsert(
            ['installation_id' => $installationId, 'loft' => $loft],
            [
                'last_ring_number' => $ringNumber,
                'dispatches_seen' => $seen,
                'updated_at' => Carbon::now(),
            ],
        );
    }

    public function lastRingNumber(string $installationId, string $loft): ?string
    {
        $row = $this->connection->table('pigeonpost_loft_cursors')
            ->where('installation_id', $installationId)
            ->where('loft', $loft)
            ->first();

        return $row->last_ring_number ?? null;
    }
}
