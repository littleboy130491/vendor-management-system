<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255|unique:vendors,company_name',
            'category_id' => 'required|exists:vendor_categories,id',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|unique:vendors,contact_email',
            'contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'tax_id' => 'nullable|string|max:50|unique:vendors,tax_id',
            'company_description' => 'nullable|string|max:1000',
            'terms_accepted' => 'required|accepted',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Company name is required.',
            'company_name.unique' => 'A vendor with this company name already exists.',
            'category_id.required' => 'Please select a vendor category.',
            'category_id.exists' => 'The selected category is invalid.',
            'contact_name.required' => 'Contact person name is required.',
            'contact_email.required' => 'Contact email is required.',
            'contact_email.email' => 'Please enter a valid email address.',
            'contact_email.unique' => 'A vendor with this email already exists.',
            'tax_id.unique' => 'A vendor with this tax ID already exists.',
            'terms_accepted.required' => 'You must accept the terms and conditions.',
            'terms_accepted.accepted' => 'You must accept the terms and conditions.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'company_name' => 'company name',
            'category_id' => 'category',
            'contact_name' => 'contact person name',
            'contact_email' => 'contact email',
            'contact_phone' => 'contact phone',
            'tax_id' => 'tax ID',
            'company_description' => 'company description',
            'terms_accepted' => 'terms and conditions',
        ];
    }
}