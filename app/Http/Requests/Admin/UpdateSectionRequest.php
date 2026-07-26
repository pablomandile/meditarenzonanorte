<?php

namespace App\Http\Requests\Admin;

use App\Support\SectionRegistry;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return SectionRegistry::rules($this->route('section')->type);
    }
}
