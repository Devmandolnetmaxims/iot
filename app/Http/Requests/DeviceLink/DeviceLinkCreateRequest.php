<?php

namespace App\Http\Requests\DeviceLink;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeviceLinkCreateRequest extends FormRequest
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
            'P1'        => 'required|string|max:10',
            'P2'        => 'required|string|max:10',
            'ori_train_number' => 'required|string',
            'des_train_number' => 'required|string',
        ];
    }

   public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Use $this->all() because CAR1, CAR2, etc. are at the root
            $input = $this->all();

            // 1. Grab only the CAR fields
            $cars = collect($input)
                ->only(['CAR1', 'CAR2', 'CAR3', 'CAR4', 'CAR5', 'CAR6', 'CAR7', 'CAR8'])
                ->filter(); // This removes all 'null' values automatically

            // 2. Compare the count of all values vs the count of unique values
            if ($cars->count() !== $cars->unique()->count()) {
                $validator->errors()->add('CAR1', 'Duplicate coach numbers (CAR) are not allowed.');
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ], 422));
    }
}
