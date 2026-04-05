<?php

namespace App\Livewire\Settings;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Language Settings')]
class Language extends Component
{
    public string $locale = 'en';

    public function mount()
    {
        $this->locale = session('locale', config('app.locale'));
    }

    public function save()
    {
        $supportedLocales = ['en', 'fr'];
        
        if (in_array($this->locale, $supportedLocales)) {
            session(['locale' => $this->locale]);
            app()->setLocale($this->locale);
            $this->dispatch('toast', message: __('Language updated successfully.'), type: 'success');
            return redirect()->route('language.edit');
        }
    }

    public function render()
    {
        return view('livewire.settings.language');
    }
}
