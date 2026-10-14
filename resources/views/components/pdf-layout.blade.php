{{--
    The print layout of the PDFs (28 · 28.3): self-contained, no scripts and
    no interactive element; the PDF engine renders it on the server.
--}}
@props(['title'])
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Geologica", "Helvetica Neue", Arial, sans-serif; color: #3a3a3a; font-size: 11pt; line-height: 1.45; margin: 0; }
        h1 { font-size: 20pt; margin: 0 0 6pt; text-transform: uppercase; font-weight: 800; }
        h2 { font-size: 14pt; margin: 18pt 0 6pt; }
        h3 { font-size: 11.5pt; margin: 12pt 0 4pt; }
        p { margin: 0 0 6pt; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .muted { color: #6b6b6b; font-size: 9.5pt; }
        .box { border-radius: 10pt; padding: 10pt 12pt; margin: 8pt 0; }
        .quote { background: #f1efe9; }
        .yellow { background: #fbe7a1; }
        .green { background: #2f6b4f; color: #fff; }
        .charcoal { background: #3a3a3a; color: #fff; }
        .coral { background: #f4876c; color: #000; }
        table { width: 100%; border-collapse: collapse; }
        td, th { text-align: left; padding: 4pt 0; border-bottom: 0.5pt solid #ddd; vertical-align: top; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
        .big { font-size: 28pt; font-weight: 800; }
    </style>
</head>
<body>
    {{ $slot }}
</body>
</html>
