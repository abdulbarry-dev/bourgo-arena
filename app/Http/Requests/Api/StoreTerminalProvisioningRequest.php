<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseFormRequest;
use App\Models\HikvisionTerminal;

class StoreTerminalProvisioningRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('provision', HikvisionTerminal::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'serial_number' => ['required', 'string', 'max:255', 'unique:hikvision_terminals,serial_number'],
            'location' => ['required', 'string', 'max:255'],
            'terminal_type' => ['required', 'in:entry,exit'],
        ];
    }
}
