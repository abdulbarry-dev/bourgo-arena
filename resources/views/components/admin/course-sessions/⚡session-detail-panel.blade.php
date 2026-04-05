<?php

use Livewire\Component;
use App\Models\CourseSession;
use App\Models\CourseSessionException;
use App\Models\Booking;
use App\Models\Member;
use App\Jobs\SendCourseCancelledPush;
use Carbon\Carbon;
use Livewire\Attributes\On;

new class extends Component
{
    public ?CourseSession $session = null;
    public ?string $date = null;
    
    public $memberIdToEnroll = '';
    
    #[On('open-session-details')]
    public function loadSession($sessionId, $date)
    {
        $this->session = CourseSession::findOrFail($sessionId);
        $this->date = $date;
        $this->memberIdToEnroll = '';
        
        \Flux\Flux::modal('session-detail-panel')->show();
    }

    public function with()
    {
        if (!$this->session || !$this->date) {
            return [
                'bookings' => collect(),
                'isCancelled' => false,
                'availableMembers' => collect(),
            ];
        }

        $isCancelled = CourseSessionException::where('course_session_id', $this->session->id)
            ->where('date', $this->date)
            ->where('is_cancelled', true)
            ->exists();

        $bookings = Booking::with('member')
            ->where('course_session_id', $this->session->id)
            ->where('date', $this->date)
            ->get();
            
        // Get members not yet enrolled
        $enrolledIds = $bookings->pluck('member_id')->toArray();
        $availableMembers = Member::whereNotIn('id', $enrolledIds)->get(['id', 'name']);

        return compact('bookings', 'isCancelled', 'availableMembers');
    }

    public function enrollMember()
    {
        if (!$this->memberIdToEnroll) return;
        
        $bookingsCount = Booking::where('course_session_id', $this->session->id)
            ->where('date', $this->date)
            ->count();
            
        if ($bookingsCount >= $this->session->capacity) {
            $this->dispatch('toast', message: 'Session is at full capacity.', type: 'danger');
            return;
        }

        // Use member_id as required by the DB table.
        Booking::create([
            'member_id' => $this->memberIdToEnroll,
            'course_session_id' => $this->session->id,
            'date' => $this->date,
            'status' => 'confirmed'
        ]);

        $this->memberIdToEnroll = '';
        $this->dispatch('toast', message: 'Member enrolled successfully!', type: 'success');
        $this->dispatch('course-session-updated');
    }

    public function removeBooking($bookingId)
    {
        Booking::where('id', $bookingId)->delete();
        $this->dispatch('toast', message: 'Booking removed.', type: 'info');
        $this->dispatch('course-session-updated');
    }

    public function cancelSessionInstance()
    {
        CourseSessionException::updateOrCreate(
            ['course_session_id' => $this->session->id, 'date' => $this->date],
            ['is_cancelled' => true]
        );

        $bookings = Booking::where('course_session_id', $this->session->id)
            ->where('date', $this->date)
            ->get();

        foreach ($bookings as $booking) {
            $booking->update(['status' => 'cancelled']);
        }

        dispatch(new SendCourseCancelledPush($this->session->id, $this->date));

        $this->dispatch('toast', message: 'Session cancelled and members notified.', type: 'success');
        $this->dispatch('course-session-updated');
        \Flux\Flux::modal('session-detail-panel')->close();
    }
};
?>

<flux:modal name="session-detail-panel" variant="flyout" class="max-w-md w-full shrink-0">
    @if($session && $date)
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $session->name }}</flux:heading>
            <flux:subheading>{{ \Carbon\Carbon::parse($date)->format('l, j M Y') }} at {{ \Carbon\Carbon::parse($session->starts_at)->format('H:i') }}</flux:subheading>
            
            <div class="mt-2 text-sm text-gray-500">
                Instructor: {{ $session->instructor }} &bull; Capacity: {{ count($bookings) }}/{{ $session->capacity }}
            </div>
        </div>

        @if($isCancelled)
            <div class="bg-red-50 text-red-600 p-4 rounded-md text-sm font-medium"> 
                <flux:badge color="red">Cancelled</flux:badge> This session instance has been cancelled.
            </div>
        @else
            <!-- Enroll Member Form -->
            <form wire:submit.prevent="enrollMember" class="space-y-4 pt-4 border-t">
                <flux:heading size="sm">Enroll Member</flux:heading>
                <div class="flex gap-2 items-end">
                    <div class="flex-1">
                        <flux:select wire:model="memberIdToEnroll" placeholder="Choose a member...">
                            @foreach($availableMembers as $member)
                                <flux:select.option value="{{ $member->id }}">{{ trim($member->name) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:button type="submit" variant="primary" :disabled="count($bookings) >= $session->capacity">Add</flux:button>
                </div>
            </form>

            <!-- Bookings List -->
            <div class="pt-4 border-t">
                <flux:heading size="sm" class="mb-3">Enrolled Members</flux:heading>
                @if(count($bookings) > 0)
                    <div class="space-y-2">
                        @foreach($bookings as $booking)
                            <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-800 p-3 rounded-md">
                                <div class="text-sm font-medium">
                                    {{ $booking->member->name ?? 'Unknown' }}
                                </div>
                                <flux:button variant="danger" size="sm" icon="trash" class="!px-2" wire:confirm="Remove this booking?" wire:click="removeBooking({{ $booking->id }})" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-sm text-gray-500 italic">No members enrolled yet.</div>
                @endif
            </div>

            <div class="pt-8 flex justify-between items-center">
                <flux:button variant="ghost" x-on:click="$flux.modal('session-detail-panel').close()">Close</flux:button>
                <flux:button variant="danger" wire:confirm="Are you sure you want to cancel this session? All enrolled members will be notified." wire:click="cancelSessionInstance">Cancel Class</flux:button>
            </div>
        @endif
    </div>
    @endif
</flux:modal>
