<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Surat Izin') }} — {{ $surat->peserta->nama }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #e5e5e5;
        }
        .print-page {
            width: 210mm;
            height: 148mm;
            display: flex;
            flex-direction: column;
            padding: 8mm 12mm;
            box-sizing: border-box;
            background: #fff;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        .logo {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo img {
            max-width: 40px;
            max-height: 40px;
        }
        .logo-placeholder {
            width: 40px;
            height: 40px;
            background: #f0f0f0;
            border: 1px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5pt;
            color: #999;
            text-align: center;
        }
        .header-title {
            text-align: center;
            flex: 1;
        }
        .header-title h1 {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.2;
        }
        .header-title h2 {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 1px;
            line-height: 1.2;
        }
        .header-title .event-name {
            font-size: 9pt;
            margin-top: 1px;
        }
        .content {
            margin-top: 4px;
            flex: 1;
        }
        .content-inner {
            display: flex;
            flex-wrap: wrap;
            gap: 1px 20px;
            align-content: flex-start;
        }
        .field-row {
            display: flex;
            width: calc(50% - 10px);
            line-height: 1.6;
        }
        .field-label {
            width: 100px;
            flex-shrink: 0;
            font-size: 11pt;
        }
        .field-sep {
            width: 10px;
            flex-shrink: 0;
            text-align: center;
            font-size: 11pt;
        }
        .field-value {
            flex: 1;
            font-size: 11pt;
        }
        .field-value .underline {
            text-decoration: underline;
        }
        .signature-area {
            margin-top: auto;
            display: flex;
            justify-content: space-around;
        }
        .signature-block {
            text-align: center;
            width: 180px;
        }
        .signature-block .label-role {
            font-size: 10pt;
            margin-bottom: 14px;
        }
        .signature-block .label-name {
            font-size: 10pt;
            margin-top: 2px;
        }
        .signature-block .signature-line {
            font-size: 10pt;
            letter-spacing: 1px;
        }
        .kop-line {
            text-align: center;
            font-size: 10pt;
            margin-bottom: 3px;
        }
        hr.kop-divider {
            border: none;
            border-top: 1.5px solid #000;
            margin-bottom: 1px;
        }
        hr.kop-sub {
            border: none;
            border-top: 0.75px solid #000;
            margin-bottom: 3px;
        }
        .date-line {
            text-align: center;
            font-size: 10pt;
            margin-top: 2px;
            margin-bottom: 2px;
        }
        @media print {
            html, body {
                width: 210mm;
                height: 148mm;
                margin: 0 !important;
                padding: 0 !important;
                background: none;
            }
            .print-page {
                width: 210mm;
                height: 148mm;
                margin: 0 !important;
                page-break-after: avoid;
                break-after: avoid-page;
                background: none;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>

@php
    $peserta = $surat->peserta;
    $kelompokNama = $peserta->kelompok->kelompok_asal ?? '-';
    $jenisPeserta = $peserta->jenis_peserta ?? '-';
    $eventLogo = config('kjam.event_logo', 'images/logo-cai.png');
    $orgLogo = config('kjam.org_logo', 'images/logo-org.png');
    $eventName = config('kjam.event_name', 'CAI');
    $hasEventLogo = $eventLogo && file_exists(public_path($eventLogo));
    $hasOrgLogo = $orgLogo && file_exists(public_path($orgLogo));
    $today = now()->locale('id')->isoFormat('D MMMM Y');
    $jenisIzinLabel = $surat->jenis_izin === 'keluar' ? 'Keluar' : 'Pulang';
@endphp

<div class="print-page">
    <div class="header">
        <div class="logo">
            @if($hasEventLogo)
                <img src="{{ asset($eventLogo) }}" alt="{{ $eventName }}">
            @else
                <div class="logo-placeholder">{{ $eventName }}</div>
            @endif
        </div>
        <div class="header-title">
            <h1>{{ __('SURAT IZIN') }}</h1>
            <h2>{{ __('MENINGGALKAN CAMPING') }} {{ strtoupper($eventName) }}</h2>
            <div class="event-name">{{ $eventName }}</div>
        </div>
        <div class="logo">
            @if($hasOrgLogo)
                <img src="{{ asset($orgLogo) }}" alt="Organization">
            @else
                <div class="logo-placeholder">{{ __('Logo') }}</div>
            @endif
        </div>
    </div>

    <hr class="kop-divider">
    <hr class="kop-sub">

    <div class="kop-line">{{ __('Nomor') }}: {{ $surat->nomor_surat ?? '-' }}</div>

    <div class="content">
        <div class="content-inner">
            <div class="field-row">
                <div class="field-label">{{ __('Nama') }}</div>
                <div class="field-sep">:</div>
                <div class="field-value"><span class="underline">{{ $peserta->nama }}</span></div>
            </div>
            <div class="field-row">
                <div class="field-label">{{ __('Umur') }}</div>
                <div class="field-sep">:</div>
                <div class="field-value">-</div>
            </div>
            <div class="field-row">
                <div class="field-label">{{ __('Kelompok') }}</div>
                <div class="field-sep">:</div>
                <div class="field-value">{{ $kelompokNama }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">{{ __('Status Kiriman') }}</div>
                <div class="field-sep">:</div>
                <div class="field-value">{{ $jenisPeserta }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">{{ __('Jenis Perizinan') }}</div>
                <div class="field-sep">:</div>
                <div class="field-value">{{ $jenisIzinLabel }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">{{ __('Keperluan') }}</div>
                <div class="field-sep">:</div>
                <div class="field-value">{{ $surat->alasan }}</div>
            </div>
            <div class="field-row">
                <div class="field-label">{{ __('Waktu Perizinan') }}</div>
                <div class="field-sep">:</div>
                <div class="field-value">
                    {{ $surat->tanggal_mulai->format('d/m/Y') }} — {{ $surat->tanggal_selesai->format('d/m/Y') }}
                </div>
            </div>
        </div>
    </div>

    <div class="date-line">{{ $eventName }}, {{ $today }}</div>

    <div class="signature-area">
        <div class="signature-block">
            <div class="label-role">{{ __('Peserta') }}</div>
            <div class="signature-line">_________________________</div>
            <div class="label-name">{{ $peserta->nama }}</div>
        </div>
        <div class="signature-block">
            <div class="label-role">{{ __('Panitia') }}</div>
            <div class="signature-line">_________________________</div>
            <div class="label-name">{{ __('( _________________ )') }}</div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
</body>
</html>
