<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2025. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\DataMapper;

use App\Enum\SyncDirection;

/**
 * QuickbooksSyncMap.
 */
class QuickbooksSyncMap
{
    public SyncDirection $direction = SyncDirection::BIDIRECTIONAL;

    // Push event settings (for PUSH direction)
    public bool $push_on_create = false;  // Push when entity is created (e.g., new client)
    public bool $push_on_update = false;  // Push when entity is updated (e.g., updated client)
    public array $push_on_statuses = [];  // Push when entity status matches (e.g., invoice statuses: ['draft', 'sent', 'paid', 'deleted'])

    public function __construct(array $attributes = [])
    {
        $this->direction = isset($attributes['direction'])
           ? SyncDirection::from($attributes['direction'])
           : SyncDirection::BIDIRECTIONAL;

        $this->push_on_create = $attributes['push_on_create'] ?? false;
        $this->push_on_update = $attributes['push_on_update'] ?? false;
        $this->push_on_statuses = $attributes['push_on_statuses'] ?? [];
    }

    public function toArray(): array
    {
        // Ensure direction is always returned as a string value, not the enum object
        $directionValue = $this->direction instanceof \App\Enum\SyncDirection 
            ? $this->direction->value 
            : (string) $this->direction;
            
        return [
            'direction' => $directionValue,
            'push_on_create' => $this->push_on_create,
            'push_on_update' => $this->push_on_update,
            'push_on_statuses' => $this->push_on_statuses,
        ];
    }
}
