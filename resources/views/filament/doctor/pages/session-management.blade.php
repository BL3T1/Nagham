<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6 space-x-2">
            <x-filament::button type="submit" color="primary">
                حفظ البيانات
            </x-filament::button>

            <x-filament::button type="button" wire:click="endSession" color="danger">
                إنهاء الجلسة
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

