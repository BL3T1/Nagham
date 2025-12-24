<x-filament-panels::page>
    <form wire:submit.prevent>
        {{ $this->form }}

        <div class="mt-4 space-x-2">
            <x-filament::button type="button" wire:click="setPrice" color="primary">
                تحديث السعر
            </x-filament::button>

            <x-filament::button type="button" wire:click="setInstallment" color="warning">
                تحديث التقسيط
            </x-filament::button>

            <x-filament::button type="button" wire:click="scheduleNextSession" color="info">
                جدولة الجلسة القادمة
            </x-filament::button>

            <x-filament::button type="button" wire:click="endSession" color="danger">
                إنهاء الجلسة
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

