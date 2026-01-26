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

namespace App\Http\Requests\Quickbooks;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class SyncQuickbooksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'clients' => [
                'present_with:invoices,quotes,payments',
                'nullable',
                function ($attribute, $value, $fail) {
                    // If value is provided and not empty, validate it
                    if ($value !== null && $value !== '' && !in_array($value, ['email', 'name'])) {
                        $fail('The ' . $attribute . ' must be one of: email, name.');
                    }
                },
            ],
            'products' => ['sometimes', 'nullable', function ($attribute, $value, $fail) {
                if ($value !== null && $value !== '' && $value !== 'product_key') {
                    $fail('The ' . $attribute . ' must be product_key.');
                }
            }],
            'invoices' => ['sometimes', 'nullable', function ($attribute, $value, $fail) {
                if ($value !== null && $value !== '' && $value !== 'number') {
                    $fail('The ' . $attribute . ' must be number.');
                }
            }],
            'quotes' => ['sometimes', 'nullable', function ($attribute, $value, $fail) {
                if ($value !== null && $value !== '' && $value !== 'number') {
                    $fail('The ' . $attribute . ' must be number.');
                }
            }],
            'payments' => 'sometimes|nullable',
            'vendors' => ['sometimes', 'nullable', function ($attribute, $value, $fail) {
                if ($value !== null && $value !== '' && !in_array($value, ['email', 'name'])) {
                    $fail('The ' . $attribute . ' must be one of: email, name.');
                }
            }],
        ];
    }

    /**
     * Prepare the data for validation.
     * Convert empty strings to null for nullable fields.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $input = $this->all();

        // Convert empty strings to null for nullable fields
        $nullableFields = ['clients', 'products', 'invoices', 'quotes', 'payments', 'vendors'];
        
        foreach ($nullableFields as $field) {
            if (isset($input[$field]) && $input[$field] === '') {
                $input[$field] = null;
            }
        }

        $this->replace($input);
    }

}
