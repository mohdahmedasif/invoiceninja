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

namespace App\Http\ValidationRules\Invoice;

use Closure;
use App\Models\Client;
use App\Models\Invoice;
use App\Utils\Traits\MakesHash;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Class VerifactuAmountCheck.
 */
class VerifactuAmountCheck implements ValidationRule
{

    use MakesHash;
    
    public function __construct(private array $input){}
    
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        if (empty($value)) {
            return;
        }

        $user = auth()->user();

        $company = $user->company();

        if ($company->verifactuEnabled()) { // Company level check if Verifactu is enabled
            
            $client = Client::withTrashed()->find($this->input['client_id']);

            $invoice = false;
            $child_invoices = false;
            $child_invoice_totals = 0;
            $child_invoice_count = 0;

            if(isset($this->input['modified_invoice_id'])) {
                $invoice = Invoice::withTrashed()->where('id', $this->decodePrimaryKey($this->input['modified_invoice_id']))->company()->firstOrFail();
                
                if ($invoice->backup->adjustable_amount <= 0) {
                    $fail("Invoice already credited in full");
                }
                
                $child_invoices = Invoice::withTrashed()
                                    ->whereIn('id', $this->transformKeys($invoice->backup->child_invoice_ids->toArray()))
                                    ->get();

                $child_invoice_totals = round($child_invoices->sum('amount'), 2);
                $child_invoice_count = $child_invoices->count();

            }

            $items = collect($this->input['line_items'])->map(function ($item) use($company){

                $discount = $item['discount'] ?? 0;
                $is_amount_discount = $this->input['is_amount_discount'] ?? true;

                if(!$is_amount_discount && $discount > 0) {
                    $discount = $item['quantity'] * $item['cost'] * ($discount / 100);
                }

                $line_total = ($item['quantity'] * $item['cost']) - $discount;

                if(!$company->settings->inclusive_taxes) {
                    $tax = ($item['tax_rate1'] ?? 0) + ($item['tax_rate2'] ?? 0) + ($item['tax_rate3'] ?? 0);
                    $tax_amount = $line_total * ($tax / 100);
                    $line_total += $tax_amount;
                }

                return $line_total;
            });

            $total_discount = $this->input['discount'] ?? 0;
            $is_amount_discount = $this->input['is_amount_discount'] ?? true;

            if(!$is_amount_discount) {
                $total_discount = $items->sum() * ($total_discount / 100);
            }

            $total = $items->sum() - $total_discount;

            if($total > 0 && $invoice) {
                $fail("Only negative invoices can be linked to existing invoices {$total}");
            }
            elseif($total < 0 && !$invoice) {
                $fail("Negative invoices {$total} can only be linked to existing invoices");
            }
            elseif($invoice && ($total + $child_invoice_totals + $invoice->amount) < 0) {
                $total_adjustments = $total + $child_invoice_totals;
                $fail("Total Adjustment {$total_adjustments} cannot exceed the original invoice amount {$invoice->amount}");
            }
        }

    }
}
