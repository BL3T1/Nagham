<x-filament-panels::page>
    <div class="space-y-4">
        <div class="text-lg font-semibold">
            {{ __('messages.todays_patients') }} ({{ $this->count }})
        </div>

        @if($this->sessions->isEmpty())
            <div class="text-center py-8 text-gray-500">
                {{ __('messages.no_patients') }}
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('messages.patient') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('messages.order') }} #
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('messages.status') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('messages.price') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                الإجراءات
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($this->sessions as $session)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $session['patient_name'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    #{{ $session['order_id'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($session['status'] === 'in_progress') bg-blue-100 text-blue-800
                                        @elseif($session['status'] === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ __("messages.{$session['status']}") }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($session['price'], 2) }} SYP
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    @if($session['status'] !== 'in_progress' && $session['status'] !== 'completed')
                                        <button wire:click="startSession({{ $session['id'] }})" 
                                            class="text-blue-600 hover:text-blue-900">
                                            بدء الجلسة
                                        </button>
                                    @endif
                                    @if($session['status'] === 'in_progress')
                                        <button wire:click="endSession({{ $session['id'] }})" 
                                            class="text-green-600 hover:text-green-900">
                                            إنهاء الجلسة
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>

