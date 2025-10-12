<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'phone' => ['nullable', 'string', 'digits:12', 'unique:users,phone'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'status' => ['nullable', 'boolean'],
            'photo_url' => ['nullable', 'string', 'max:255'],
            'mobile_app_firebase_token' => ['nullable', 'string'],
            'admin_dashboard_firebase_token' => ['nullable', 'string'],
            'role' => ['required', 'string', 'in:System Admin,Customer'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'username.unique' => 'This username is already taken.',
            'phone.digits' => 'The phone number must be exactly 12 digits.',
            'phone.unique' => 'This phone number is already registered.',
            'password.required' => 'The password field is required.',
            'password.confirmed' => 'The password confirmation does not match.',
            'gender.in' => 'The gender must be male, female, or other.',
            'role.required' => 'The role field is required.',
            'role.in' => 'The role must be either System Admin or Customer.',
        ];
    }
}
