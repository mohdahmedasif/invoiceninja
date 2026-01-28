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

use App\Models\Product;

/**
 * QuickbooksSync.
 *
 * Product type to income account mapping:
 * Keys are Product::PRODUCT_TYPE_* constants (int). Values are QuickBooks account IDs (string|null).
 * Example: [Product::PRODUCT_TYPE_SERVICE => '123', Product::PRODUCT_TYPE_PHYSICAL => '456']
 * Null values indicate the account has not been configured for that product type.
 */
class QuickbooksSync
{
    public QuickbooksSyncMap $client;

    public QuickbooksSyncMap $vendor;

    public QuickbooksSyncMap $invoice;

    public QuickbooksSyncMap $sales;

    public QuickbooksSyncMap $quote;

    public QuickbooksSyncMap $purchase_order;

    public QuickbooksSyncMap $product;

    public QuickbooksSyncMap $payment;

    public QuickbooksSyncMap $expense;

    /**
     * Map of product type id (Product::PRODUCT_TYPE_*) to QuickBooks income account ID.
     * E.g. [2 => '123', 1 => '456']
     * Null values indicate the account has not been configured for that product type.
     *
     * @var array<int, string|null>
     */
    public array $income_account_map = [];

    public function __construct(array $attributes = [])
    {
        $this->client = new QuickbooksSyncMap($attributes['client'] ?? []);
        $this->vendor = new QuickbooksSyncMap($attributes['vendor'] ?? []);
        $this->invoice = new QuickbooksSyncMap($attributes['invoice'] ?? []);
        $this->sales = new QuickbooksSyncMap($attributes['sales'] ?? []);
        $this->quote = new QuickbooksSyncMap($attributes['quote'] ?? []);
        $this->purchase_order = new QuickbooksSyncMap($attributes['purchase_order'] ?? []);
        $this->product = new QuickbooksSyncMap($attributes['product'] ?? []);
        $this->payment = new QuickbooksSyncMap($attributes['payment'] ?? []);
        $this->expense = new QuickbooksSyncMap($attributes['expense'] ?? []);
        $this->income_account_map = $attributes['income_account_map'] ?? [];
    }

    /**
     * Suggested default mapping of Product::PRODUCT_TYPE_* to QuickBooks income account IDs.
     * Returns null for all types, indicating they need to be configured.
     * Use when building UI defaults or onboarding; stored config overrides these.
     *
     * @return array<int, null>
     */
    public static function defaultProductTypeIncomeAccountMap(): array
    {
        return [
            Product::PRODUCT_TYPE_PHYSICAL => null,
            Product::PRODUCT_TYPE_SERVICE => null,
            Product::PRODUCT_TYPE_DIGITAL => null,
            Product::PRODUCT_TYPE_SHIPPING => null,
            Product::PRODUCT_TYPE_EXEMPT => null,
            Product::PRODUCT_TYPE_REDUCED_TAX => null,
            Product::PRODUCT_TYPE_OVERRIDE_TAX => null,
            Product::PRODUCT_TYPE_ZERO_RATED => null,
            Product::PRODUCT_TYPE_REVERSE_TAX => null,
            Product::PRODUCT_INTRA_COMMUNITY => null,
        ];
    }

    public function toArray(): array
    {
        return [
            'client' => $this->client->toArray(),
            'vendor' => $this->vendor->toArray(),
            'invoice' => $this->invoice->toArray(),
            'sales' => $this->sales->toArray(),
            'quote' => $this->quote->toArray(),
            'purchase_order' => $this->purchase_order->toArray(),
            'product' => $this->product->toArray(),
            'payment' => $this->payment->toArray(),
            'expense' => $this->expense->toArray(),
            'income_account_map' => $this->income_account_map,
        ];
    }
}
