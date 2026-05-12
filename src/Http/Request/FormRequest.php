<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Http\Request;

use Hyperf\Validation\Request\FormRequest as HyperfFormRequest;

abstract class FormRequest extends HyperfFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function validated(): array
    {
        return $this->normalize(parent::validated());
    }

    protected function normalize(array $data): array
    {
        return $data;
    }
}
