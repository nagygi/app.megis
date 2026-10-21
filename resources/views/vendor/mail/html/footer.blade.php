@props(['tagline' => null])
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="footer-cell" align="center">
<table class="footer-rule" align="center" width="32" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="footer-rule-line">&nbsp;</td>
</tr>
</table>
@if ($tagline)
<p class="footer-tagline">{{ $tagline }}</p>
@endif
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
</td>
</tr>
