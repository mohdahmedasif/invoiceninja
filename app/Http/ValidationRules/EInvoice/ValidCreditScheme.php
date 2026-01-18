<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2025. Invoice Ninja LLC (https://invoiceninja.com)
 *1`
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Http\ValidationRules\EInvoice;

use App\Services\EDocument\Standards\Validation\Peppol\CreditLevel;
use Closure;
use InvoiceNinja\EInvoice\EInvoice;
use Illuminate\Validation\Validator;
use InvoiceNinja\EInvoice\Models\Peppol\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;

/**
 * Class ValidScheme.
 */
class ValidCreditScheme implements ValidationRule, ValidatorAwareRule
{
    /**
     * The validator instance.
     *
     * @var Validator
     */
    protected $validator;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {


        if (isset($value['CreditNote'])) {

            $r = new EInvoice();

            if (data_get($value, 'CreditNote.InvoiceDocumentReference.IssueDate') === null ||
                data_get($value, 'CreditNote.InvoiceDocumentReference.IssueDate') === '') {
                    unset($value['CreditNote']['InvoiceDocumentReference']['IssueDate']);
                }
            
            $errors = $r->validateRequest($value['CreditNote'], CreditLevel::class);

            foreach ($errors as $key => $msg) {

                $this->validator->errors()->add(
                    "e_invoice.{$key}",
                    "{$key} - {$msg}"
                );

            }


            if (data_get($value, 'CreditNote.InvoiceDocumentReference.ID') === null ||
                data_get($value, 'CreditNote.InvoiceDocumentReference.ID') === '') {
                
                $this->validator->errors()->add(
                    "e_invoice.InvoiceDocumentReference.ID",
                    "Invoice Reference/Number is required"
                );

            }

            if (isset($value['CreditNote']['InvoiceDocumentReference']['IssueDate']) && strlen($value['CreditNote']['InvoiceDocumentReference']['IssueDate']) > 1 && !$this->isValidDateSyntax($value['CreditNote']['InvoiceDocumentReference']['IssueDate'])) {

                $this->validator->errors()->add(
                    "e_invoice.InvoiceDocumentReference.IssueDate",
                    "Invoice Issue Date is required"
                );

            }
            

        }

    }

    private function isValidDateSyntax(string $date_string): bool
    {
        try {
            $date = date_create($date_string);
            return $date !== false && $date instanceof \DateTime;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Set the current validator.
     */
    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }


}
