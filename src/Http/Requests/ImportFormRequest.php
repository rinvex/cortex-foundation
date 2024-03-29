<?php

declare(strict_types=1);

namespace Cortex\Foundation\Http\Requests;

use Cortex\Foundation\Http\FormRequest;

class ImportFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'file.*' => 'required|file|mimetypes:'.implode(',', config('cortex.foundation.datatables.imports')),
        ];
    }
}
