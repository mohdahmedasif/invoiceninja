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
 * QuickbooksSync.
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

    public QuickbooksPushEvents $push_events;

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
        $this->push_events = new QuickbooksPushEvents($attributes['push_events'] ?? []);
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
            'push_events' => $this->push_events->toArray(),
        ];
    }
}
