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
 * Keys are Product::PRODUCT_TYPE_* constants (int). Values are income account names (string).
 * Example: [Product::PRODUCT_TYPE_SERVICE => 'Service Income', Product::PRODUCT_TYPE_PHYSICAL => 'Sales of Product Income']
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

    public string $default_income_account = '';

    public string $default_expense_account = '';

    /**
     * Map of product type id (Product::PRODUCT_TYPE_*) to income account name.
     * E.g. [2 => 'Service Income', 1 => 'Sales of Product Income']
     *
     * @var array<int, string>
     */
    public array $product_type_income_account_map = [];

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
        $this->default_income_account = $attributes['default_income_account'] ?? '';
        $this->default_expense_account = $attributes['default_expense_account'] ?? '';
        $map = $attributes['product_type_income_account_map'] ?? [];
        $this->product_type_income_account_map = self::normalizeProductTypeIncomeAccountMap($map);
    }

    /**
     * Normalize product_type_income_account_map so keys are int (product type ids).
     */
    private static function normalizeProductTypeIncomeAccountMap(mixed $map): array
    {
        if (! is_array($map)) {
            return [];
        }
        $out = [];
        foreach ($map as $k => $v) {
            if (is_string($v) && $v !== '') {
                $out[(int) $k] = $v;
            }
        }
        return $out;
    }

    /**
     * Suggested default mapping of Product::PRODUCT_TYPE_* to common QuickBooks income account names.
     * Use when building UI defaults or onboarding; stored config overrides these.
     */
    public static function defaultProductTypeIncomeAccountMap(): array
    {
        return [
            Product::PRODUCT_TYPE_PHYSICAL => 'Sales of Product Income',
            Product::PRODUCT_TYPE_SERVICE => 'Service Income',
            Product::PRODUCT_TYPE_DIGITAL => 'Sales of Product Income',
            Product::PRODUCT_TYPE_SHIPPING => 'Shipping and Delivery Income',
            Product::PRODUCT_TYPE_EXEMPT => 'Sales of Product Income',
            Product::PRODUCT_TYPE_REDUCED_TAX => 'Sales of Product Income',
            Product::PRODUCT_TYPE_OVERRIDE_TAX => 'Sales of Product Income',
            Product::PRODUCT_TYPE_ZERO_RATED => 'Sales of Product Income',
            Product::PRODUCT_TYPE_REVERSE_TAX => 'Sales of Product Income',
            Product::PRODUCT_INTRA_COMMUNITY => 'Sales of Product Income',
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
            'default_income_account' => $this->default_income_account,
            'default_expense_account' => $this->default_expense_account,
            'product_type_income_account_map' => $this->product_type_income_account_map,
        ];
    }
}
