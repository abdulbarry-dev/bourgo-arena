<div x-cloak x-data="confirmModal()" x-init="init()" @keydown.escape.window="close()" class="fixed inset-0 z-50 flex items-end justify-center sm:items-center">
    <div x-show="open" class="fixed inset-0 bg-black/50 transition-opacity" @click="close()"></div>

    <div x-show="open" x-transition class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 sm:mx-0 z-50" role="dialog" aria-modal="true">
        <div class="p-4">
            <h3 class="text-lg font-medium" x-text="title"></h3>
            <p class="mt-2 text-sm text-gray-600" x-text="message"></p>
        </div>

        <div class="px-4 py-3 bg-gray-50 flex justify-end gap-2">
            <button type="button" @click="close()" :disabled="loading" class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium bg-white border">Cancel</button>

            <button
                type="button"
                :class="actionType === 'danger' ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-blue-600 text-white hover:bg-blue-700'"
                class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium disabled:opacity-60"
                @click="confirm()"
                :disabled="loading"
            >
                <template x-if="!loading">
                    <span x-text="confirmText"></span>
                </template>
                <template x-if="loading">
                    <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    <span>Processing...</span>
                </template>
            </button>
        </div>
    </div>

    <script>
        function confirmModal(){
            return {
                open: false,
                title: 'Confirm',
                message: '',
                confirmText: 'Confirm',
                actionType: 'primary',
                payload: {},
                nonce: null,
                loading: false,

                init(){
                    window.addEventListener('confirm:open', (e) => {
                        this.title = e.detail.title ?? this.title;
                        this.message = e.detail.message ?? this.message;
                        this.confirmText = e.detail.confirmText ?? this.confirmText;
                        this.actionType = e.detail.actionType ?? this.actionType;
                        this.payload = e.detail.payload ?? {};
                        this.nonce = e.detail.nonce ?? null;
                        this.open = true;
                    });

                    window.addEventListener('confirm:complete', (e) => {
                        this.loading = false;
                        this.open = false;
                    });
                },

                close(){
                    if(this.loading) return;
                    this.open = false;
                },

                confirm(){
                    this.loading = true;
                    const detail = { actionPayload: this.payload, nonce: this.nonce };
                    // Livewire consumers
                    if (window.Livewire && typeof Livewire.emit === 'function') {
                        Livewire.emit('confirm:confirmed', detail);
                    }

                    // DOM consumers
                    window.dispatchEvent(new CustomEvent('confirm:confirmed', { detail }));
                }
            }
        }
    </script>
</div>
