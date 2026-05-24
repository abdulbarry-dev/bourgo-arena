<?php

namespace App\Livewire\Admin\AccessControl;

use App\Jobs\NotifyMemberCardSuspended;
use App\Models\CheckInEvent;
use App\Models\NfcCard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
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
            // CheckInEvent doesn't have updated_at
            $event->is_suspicious = false;
            $event->save();
            $this->loadAlerts();
        }
    }

    public function dismissAllAlerts()
    {
        $suspiciousEventIds = $this->alerts->pluck('id');
        CheckInEvent::whereIn('id', $suspiciousEventIds)->update(['is_suspicious' => false]);
        $this->loadAlerts();
    }

    public function escalateAndSuspend($cardUid)
    {
        $card = NfcCard::with('member')->where('uid', $cardUid)->first();
        if ($card) {
            $card->status = 'suspended';
            $card->save();

            if ($card->member) {
                // Log logic / change member status to pending review
                Log::info("Admin manually suspended card due to anti-passback. Member: {$card->member->id}");
            }

            // Dispatch notification job
            NotifyMemberCardSuspended::dispatch($card);
        }
        $this->loadAlerts();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.admin.access-control.anti-passback-alerts');
    }
}
