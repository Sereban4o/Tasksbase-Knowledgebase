<?php

namespace App\Services\Integration\OneC;

use App\Models\IntegrationMapping;

/**
 * Maps local entities to their 1С external identifiers.
 */
class MappingRepository
{
    public function __construct(private string $integration = 'one_c')
    {
    }

    public function findLocalId(string $entityType, string $externalId): ?int
    {
        $mapping = IntegrationMapping::where('integration', $this->integration)
            ->where('entity_type', $entityType)
            ->where('external_id', $externalId)
            ->first();

        return $mapping?->entity_id;
    }

    public function findExternalId(string $entityType, int $entityId): ?string
    {
        return IntegrationMapping::where('integration', $this->integration)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->value('external_id');
    }

    /**
     * Batch map local ids -> external ids in a single query (avoids N+1).
     *
     * @param  array<int, int>  $entityIds
     * @return array<int, string|null> keyed by entity_id
     */
    public function externalIdsFor(string $entityType, array $entityIds): array
    {
        if (empty($entityIds)) {
            return [];
        }

        return IntegrationMapping::where('integration', $this->integration)
            ->where('entity_type', $entityType)
            ->whereIn('entity_id', $entityIds)
            ->pluck('external_id', 'entity_id')
            ->all();
    }

    public function link(string $entityType, int $entityId, ?string $externalId, array $meta = []): IntegrationMapping
    {
        return IntegrationMapping::updateOrCreate(
            [
                'integration' => $this->integration,
                'entity_type' => $entityType,
                'external_id' => $externalId,
            ],
            [
                'entity_id' => $entityId,
                'meta' => $meta ?: null,
            ],
        );
    }
}
