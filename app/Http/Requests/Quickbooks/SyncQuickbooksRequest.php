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
                'required_with:invoices,quotes,payments',
                'nullable',
                function ($attribute, $value, $fail) {
                    // Normalize empty string to 'create' for validation
                    $normalizedValue = ($value === '') ? 'create' : $value;
                    
                    // If value is provided (not null), validate it
                    if ($normalizedValue !== null && !in_array($normalizedValue, ['email', 'name', 'create'])) {
                        $fail('The ' . $attribute . ' must be one of: email, name, create.');
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
     * Configure the validator instance.
     * Normalize empty strings to 'create' before validation.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        // Normalize empty strings to 'create' BEFORE validation runs
        // This ensures required_with sees a value instead of empty strings
        $data = $validator->getData();
        $fieldsToNormalize = ['clients', 'products', 'invoices', 'quotes', 'payments', 'vendors'];
        
        $normalizedData = $data;
        foreach ($fieldsToNormalize as $field) {
            if (isset($normalizedData[$field]) && $normalizedData[$field] === '') {
                $normalizedData[$field] = 'create';
            }
        }
        
        // Update the validator's data BEFORE validation rules run
        if ($normalizedData !== $data) {
            $validator->setData($normalizedData);
        }
    }

    /**
     * Prepare the data for validation.
     * Convert empty strings to 'create' for nullable fields.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $input = $this->all();

        // Convert empty strings to 'create' for nullable fields
        $fieldsToNormalize = ['clients', 'products', 'invoices', 'quotes', 'payments', 'vendors'];
        
        foreach ($fieldsToNormalize as $field) {
            if (isset($input[$field]) && $input[$field] === '') {
                $input[$field] = 'create';
            }
        }

        $this->replace($input);
    }

}
