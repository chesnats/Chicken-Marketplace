<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'seller_default_location' => $this->user() && $this->user()->role === 'seller'
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'seller_contact_number' => $this->user() && $this->user()->role === 'seller'
                ? ['required', 'string', 'max:20']
                : ['nullable', 'string', 'max:20'],
        ];
    }
}


