<?php

namespace App\Livewire\Admin\Analytics;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.analytics.dashboard');
    }
}
    