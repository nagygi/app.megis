<div style="font-family: Arial, sans-serif; font-size: 8pt; color: #6b6b6b; width: 100%; padding: 0 16mm; display: flex; justify-content: space-between;">
    {{-- The header is its own page in Chrome: the logo goes in as data (28 · 28.3). --}}
    <span style="display: flex; align-items: center; gap: 6px;"><img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/mail/logo.png'))) }}" alt="mégis" style="height: 9pt;"> · {{ $title }}</span>
    <span>{{ $date->format('Y.m.d.') }}</span>
</div>
