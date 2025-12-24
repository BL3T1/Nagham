<x-filament-panels::page>
    <form wire:submit="process">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" color="success" size="lg">
                معالجة الدفع
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

