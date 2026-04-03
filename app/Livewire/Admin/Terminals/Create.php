<?php

namespace App\Livewire\Admin\Terminals;

use App\Models\HikvisionTerminal;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Add Hardware Terminal')]
class Create extends Component
{
    public string $name = '';

    public string $ip_address = '';

    public string $serial_number = '';

    public string $location = '';

    public string $terminal_type = 'entry';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ipv4'],
            'serial_number' => ['required', 'string', 'max:255', 'unique:hikvision_terminals,serial_number'],
            'location' => ['required', 'string', 'max:255'],
            'terminal_type' => ['required', 'in:entry,exit'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $terminal = HikvisionTerminal::create([
            'name' => $this->name,
            'ip_address' => $this->ip_address,
            'serial_number' => $this->serial_number,
            'location' => $this->location,
            'terminal_type' => $this->terminal_type,
            'api_token' => Str::random(60),
            'status' => 'offline',
        ]);

        $this->dispatch(
            'toast',
            message: __('Terminal created successfully. API token has been automatically generated.'),
            type: 'success'
        );

        $this->redirectRoute('admin.terminals.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.terminals.create');
    }
}
