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

/**
 * QuickbooksPushEvents.
 * 
 * Stores push event configuration for QuickBooks integration.
 * This class provides a clean separation of push event settings.
 */
class QuickbooksPushEvents
{
    /**
     * Push when a new client is created.
     */
    public bool $push_on_new_client = false;

    /**
     * Push when an existing client is updated.
     */
    public bool $push_on_updated_client = false;

    /**
     * Push when an invoice status matches one of these values.
     * 
     * Valid values: 'draft', 'sent', 'paid', 'deleted'
     * 
     * @var array<string>
     */
    public array $push_invoice_statuses = [];

    public function __construct(array $attributes = [])
    {
        $this->push_on_new_client = $attributes['push_on_new_client'] ?? false;
        $this->push_on_updated_client = $attributes['push_on_updated_client'] ?? false;
        $this->push_invoice_statuses = $attributes['push_invoice_statuses'] ?? [];
    }

    public function toArray(): array
    {
        return [
            'push_on_new_client' => $this->push_on_new_client,
            'push_on_updated_client' => $this->push_on_updated_client,
            'push_invoice_statuses' => $this->push_invoice_statuses,
        ];
    }
}
