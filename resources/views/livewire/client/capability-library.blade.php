{{-- Tárak → Képességtár (M3, NM-12): one row a capability, divided by a line; it opens in place. --}}
<div class="flex max-w-2xl flex-col gap-6">
    @if ($rows === [])
        <x-ui.empty-state symbol="fire">{{ __('capabilities.library.empty') }}</x-ui.empty-state>
    @else
        <ul class="flex flex-col divide-y divide-grey-200">
            @foreach ($rows as $row)
                @php($isOpen = $openId === $row['id'])
                <li wire:key="library-capability-{{ $row['id'] }}">
                    <x-ui.list-row
                        :expanded="$isOpen"
                        wire:click="toggle({{ $row['id'] }})"
                        :title="$row['name']"
                        :meta="\App\Services\Capabilities\CapabilityText::line($row)"
                        :meta-tone="$row['state'] === \App\Enums\CapabilityState::Absent ? 'coral' : null"
                    />
                    @if ($isOpen)
                        <div class="flex flex-col gap-3 pb-5 text-sm">
                            @if ($row['blocker'])
                                <p class="text-coral-ink">{{ __('capabilities.library.blocker', ['name' => $row['blocker']]) }}</p>
                            @endif
                            @if (filled($row['definition']))
                                <div>
                                    <p class="font-semibold text-grey-700">{{ __('capabilities.library.meaning') }}</p>
                                    <p class="text-base">{{ $row['definition'] }}</p>
                                </div>
                            @endif
                            @if (filled($row['statement']))
                                <div>
                                    <p class="font-semibold text-grey-700">{{ __('capabilities.library.statement') }}</p>
                                    <p class="text-base">{{ $row['statement'] }}</p>
                                </div>
                            @endif
                            @if ($row['quote'])
                                {{-- The grey quote box of the report (prototype .qt): role and date, never a name. --}}
                                <figure class="flex flex-col gap-1 rounded-[18px] bg-grey-100 px-3.5 py-3">
                                    <blockquote class="text-sm leading-relaxed text-charcoal">„{{ $row['quote']['text'] }}”</blockquote>
                                    <figcaption class="text-xs text-grey-700">{{ collect([$row['quote']['who'], $row['quote']['date']])->filter()->implode(' · ') }}</figcaption>
                                </figure>
                            @endif
                            @if ($row['little_data'])
                                <p class="text-grey-700">{{ __('capabilities.library.more_data') }}</p>
                            @endif
                            <p class="text-grey-700">{{ __('capabilities.library.maturity_later') }}</p>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
