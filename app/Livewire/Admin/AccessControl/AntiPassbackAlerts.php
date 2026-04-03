<?php

namespace App\Livewire\Admin\AccessControl;

use App\Models\CheckInEvent;
use App\Models\NfcCard;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class AntiPassbackAlerts extends Component
{
    public Collection $alerts;

    public function mount()
    {
        $this->loadAlerts();
    }

    #[On('echo-private:checkins,CheckInProcessed')]
    public function handleNewCheckIn($event)
    {
        // Re-load alerts on every check-in
        $this->loadAlerts();
    }

    public function loadAlerts()
    {
        // We look for 'is_suspicious' check-in events
        $this->alerts = CheckInEvent::with(['member', 'terminal'])
            ->where('is_suspicious', true)
            ->where('result', 'denied') // usually denied or flagged
            ->latest('checked_in_at')
            ->limit(30)
            ->get();
    }

    public function dismissAlert($eventId)
    {
        $event = CheckInEvent::find($eventId);
        if ($event) {
            // DB does not allow 'updated_at' update but we want to mark it as not suspicious manually
            // In a strict append-only setup, we might insert an 'AuditDismiss' record instead.
            // For simplicity in UI logic:
            $event->is_suspicious = false;
            $event->save();
            $this->loadAlerts();
        }
    }

    public function escalateAndSuspend($cardUid)
    {
        $card = NfcCard::where('uid', $cardUid)->first();
        if ($card) {
            $card->status = 'suspended';
            $card->save();

            if ($card->member) {
                // Log logic / change member status to pending review
            }
        }
        $this->loadAlerts();
    }

    public function render()
    {
        return view('livewire.admin.access-control.anti-passback-alerts')->layout('layouts.app');
    }
}
