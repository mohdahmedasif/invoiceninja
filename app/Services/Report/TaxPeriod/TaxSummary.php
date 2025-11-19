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

namespace App\Services\Report\TaxPeriod;

/**
 * Data Transfer Object for tax summary information
 */
class TaxSummary
{
    public function __construct(
        public float $taxable_amount,
        public float $total_taxes,
        public TaxReportStatus $status,
        public float $adjustment = 0,
        public float $tax_adjustment = 0,
    ) {
    }

    /**
     * Create from transaction event metadata
     */
    public static function fromMetadata(object $metadata): self
    {
        return new self(
            taxable_amount: $metadata->taxable_amount ?? 0,
            total_taxes: $metadata->total_taxes ?? 0,
            status: TaxReportStatus::from($metadata->status ?? 'updated'),
            adjustment: $metadata->adjustment ?? 0,
            tax_adjustment: $metadata->tax_adjustment ?? 0,
        );
    }

    /**
     * Calculate total tax paid based on invoice payment ratio
     *
     * @param float $invoice_amount Total invoice amount
     * @param float $invoice_paid_to_date Amount paid on invoice
     * @return float Tax amount that has been paid
     */
    public function calculateTotalPaid(float $invoice_amount, float $invoice_paid_to_date): float
    {
        if ($invoice_amount == 0) {
            return 0;
        }

        return round($this->total_taxes * ($invoice_paid_to_date / $invoice_amount), 2);
    }

    /**
     * Calculate total tax remaining
     *
     * @param float $invoice_amount Total invoice amount
     * @param float $invoice_paid_to_date Amount paid on invoice
     * @return float Tax amount still outstanding
     */
    public function calculateTotalRemaining(float $invoice_amount, float $invoice_paid_to_date): float
    {
        return round($this->total_taxes - $this->calculateTotalPaid($invoice_amount, $invoice_paid_to_date), 2);
    }

    /**
     * Get the payment ratio for this invoice
     *
     * @param float $invoice_amount Total invoice amount
     * @param float $invoice_paid_to_date Amount paid on invoice
     * @return float Ratio of amount paid (0 to 1)
     */
    public function getPaymentRatio(float $invoice_amount, float $invoice_paid_to_date): float
    {
        return $invoice_amount > 0 ? $invoice_paid_to_date / $invoice_amount : 0;
    }

    /**
     * Convert to array for spreadsheet export
     */
    public function toArray(): array
    {
        return [
            'taxable_amount' => $this->taxable_amount,
            'total_taxes' => $this->total_taxes,
            'status' => $this->status->value,
            'adjustment' => $this->adjustment,
            'tax_adjustment' => $this->tax_adjustment,
        ];
    }
}
