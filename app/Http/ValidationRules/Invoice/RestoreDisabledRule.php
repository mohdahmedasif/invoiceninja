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
use App\Models\Invoice;
use App\Utils\Traits\MakesHash;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Class RestoreDisabledRule.
 */
class RestoreDisabledRule implements ValidationRule
{
    use MakesHash;
    
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $user = auth()->user();
        $company = $user->company();


        if (empty($value) || !$company->verifactuEnabled()) {
            return;
        }

        $base_query = Invoice::withTrashed()
                            ->whereIn('id', $this->transformKeys(request()->ids))
                            ->company();

        $restore_query = clone $base_query;
        $delete_query = clone $base_query;

        $mutated_query = $delete_query->where(function ($q){
            $q->where('backup->document_type', 'F1')->where('backup->child_invoice_ids', '!=', '[]');
        });

        /** For verifactu, we do not allow restores of deleted invoices */
        if($value == 'restore' && $restore_query->where('is_deleted', true)->exists()) {
            $fail(ctrans('texts.restore_disabled_verifactu'));
        }
        elseif(in_array($value, ['delete', 'cancel']) && $mutated_query->exists()) {
            $fail(ctrans('texts.delete_disabled_verifactu'));
        }
    }
    
}
