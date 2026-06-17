<?php

namespace App\Http\Requests\Traits;

trait NormalizesPrivilegeKeysInput
{
    protected function prepareForValidation(): void
    {
        if (!$this->has('privileges')) {
            return;
        }

        $this->merge([
            'privileges' => $this->normalizePrivilegeKeys($this->input('privileges', [])),
        ]);
    }

    /**
     * @param  array<int, mixed>  $privileges
     * @return array<int, string>
     */
    protected function normalizePrivilegeKeys(array $privileges): array
    {
        return collect($privileges)
            ->map(fn (mixed $privilege): mixed => is_array($privilege) ? ($privilege['key'] ?? null) : $privilege)
            ->filter()
            ->values()
            ->all();
    }
}
