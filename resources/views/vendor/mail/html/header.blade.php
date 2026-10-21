@props(['url'])
<tr>
<td class="header">
<table class="header-inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="header-cell">
<a href="{{ $url }}" target="_blank" rel="noopener">
{{-- A PNG: Gmail and Outlook do not show SVG. The alt text stands in when images are blocked. --}}
<img src="{{ asset('images/mail/logo.png') }}" class="logo" width="112" height="47" alt="{{ __('ui.brand') }}">
</a>
</td>
</tr>
</table>
</td>
</tr>
