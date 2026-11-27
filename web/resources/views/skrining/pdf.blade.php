<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan SPK DiaXplain — {{ $screening->id }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.45; margin: 20px; }
        .header { border-bottom: 2px solid #0d9488; padding-bottom: 10px; margin-bottom: 14px; }
        .title { font-size: 18px; font-weight: bold; color: #0f172a; margin: 0; }
        .subtitle { font-size: 10px; color: #475569; margin-top: 2px; }
        .disclaimer { background: #fffbeb; border: 1px solid #f59e0b; padding: 8px 10px; border-radius: 6px; font-size: 9.5px; color: #78350f; margin-bottom: 14px; }
        .box { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; }
        .section-title { font-size: 12px; font-weight: bold; color: #0f766e; margin-top: 0; margin-bottom: 6px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 7px; text-align: left; font-size: 10px; }
        th { background: #f1f5f9; font-weight: bold; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; background: #ccfbf1; color: #115e59; }
        .footer { margin-top: 18px; border-top: 1px solid #cbd5e1; padding-top: 8px; font-size: 9px; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">DiaXplain — Laporan Sistem Pendukung Keputusan Skrining Status Diabetes</p>
        <p class="subtitle">
            ID Kasus: {{ $screening->patient_label ?: ('SCR-' . strtoupper(substr($screening->id, 0, 8))) }} |
            Tanggal: {{ $screening->created_at?->format('d M Y, H:i') }} WIB |
            Versi Model: {{ $prediction?->model_version_tag ?? 'xgb-v1.0.0' }}
        </p>
    </div>

    <div class="disclaimer">
        <strong>PERINGATAN MEDIS (BUKAN ALAT DIAGNOSIS):</strong> {{ $disclaimer }}
    </div>

    <div class="box">
        <p class="section-title">1. Hasil Klasifikasi Model XGBoost</p>
        <p>
            Status Prediksi: <span class="badge">{{ $prediction?->label_id ?? $prediction?->label }} ({{ $prediction?->label }})</span>
            &nbsp;|&nbsp; Tingkat Keyakinan Model: <strong>{{ ucfirst($prediction?->confidence_note ?? 'sedang') }}</strong>
        </p>
        <table>
            <thead>
                <tr>
                    <th>Tidak Diabetes (No Diabetes — Kelas 0)</th>
                    <th>Prediabetes (Kelas 1)</th>
                    <th>Diabetes (Kelas 2)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>{{ number_format(((float) ($prediction?->probabilities['No Diabetes'] ?? 0)) * 100, 2, ',', '.') }}%</strong></td>
                    <td><strong>{{ number_format(((float) ($prediction?->probabilities['Prediabetes'] ?? 0)) * 100, 2, ',', '.') }}%</strong></td>
                    <td><strong>{{ number_format(((float) ($prediction?->probabilities['Diabetes'] ?? 0)) * 100, 2, ',', '.') }}%</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="box">
        <p class="section-title">2. Faktor Kontribusi Fitur Lokal (Explainable AI — TreeSHAP Top-{{ count($prediction?->shap_local['top'] ?? []) }})</p>
        <table>
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Indikator Kesehatan</th>
                    <th>Nilai Input</th>
                    <th>Nilai SHAP (Log-Odds)</th>
                    <th>Arah Pengaruh</th>
                </tr>
            </thead>
            <tbody>
                @foreach($prediction?->shap_local['top'] ?? [] as $sh)
                    <tr>
                        <td>#{{ $sh['rank'] }}</td>
                        <td>{{ $sh['label_id'] }} ({{ $sh['feature'] }})</td>
                        <td>{{ $sh['display'] }}</td>
                        <td>{{ ($sh['direction'] ?? 'naik') === 'naik' ? '+' : '' }}{{ number_format((float) $sh['shap'], 4) }}</td>
                        <td>{{ ($sh['direction'] ?? 'naik') === 'naik' ? '▲ Meningkatkan kecenderungan' : '▼ Menahan / menurunkan' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($interpretation && $interpretation->narrative)
        @php $nar = $interpretation->narrative; @endphp
        <div class="box">
            <p class="section-title">3. Penjelasan Naratif Tervalidasi ({{ $interpretation->used_fallback ? 'Templat Deterministik' : ('LLM: ' . $interpretation->provider) }})</p>
            <p><strong>Ringkasan:</strong> {{ $nar['ringkasan'] ?? '' }}</p>

            @if(!empty($nar['faktor_yang_meningkatkan']))
                <p><strong>Faktor Pendorong Utama:</strong></p>
                <ul>
                    @foreach($nar['faktor_yang_meningkatkan'] as $f)
                        <li><strong>[{{ $f['feature'] }}]</strong> {{ $f['penjelasan'] }}</li>
                    @endforeach
                </ul>
            @endif

            @if(!empty($nar['faktor_yang_menurunkan']))
                <p><strong>Faktor Penahan:</strong></p>
                <ul>
                    @foreach($nar['faktor_yang_menurunkan'] as $f)
                        <li><strong>[{{ $f['feature'] }}]</strong> {{ $f['penjelasan'] }}</li>
                    @endforeach
                </ul>
            @endif

            @if(!empty($nar['saran_umum']))
                <p><strong>Saran Gaya Hidup Umum:</strong></p>
                <ul>
                    @foreach($nar['saran_umum'] as $s)
                        <li>{{ $s }}</li>
                    @endforeach
                </ul>
            @endif

            <p><strong>Rekomendasi Tindak Lanjut Medis:</strong> {{ $nar['kapan_ke_tenaga_kesehatan'] ?? '' }}</p>
        </div>
    @endif

    <div class="box">
        <p class="section-title">4. Ringkasan 21 Indikator Kesehatan Masukan (Standar BRFSS)</p>
        <table>
            <thead>
                <tr>
                    <th>Fitur</th>
                    <th>Nilai</th>
                    <th>Fitur</th>
                    <th>Nilai</th>
                </tr>
            </thead>
            <tbody>
                @php $chunks = array_chunk($prediction?->input_summary ?? [], 2); @endphp
                @foreach($chunks as $pair)
                    <tr>
                        <td>{{ $pair[0]['label_id'] ?? '' }}</td>
                        <td><strong>{{ $pair[0]['display'] ?? '' }}</strong></td>
                        <td>{{ $pair[1]['label_id'] ?? '-' }}</td>
                        <td><strong>{{ $pair[1]['display'] ?? '-' }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Dihasilkan oleh DiaXplain (XGBoost + TreeSHAP + LLM) — Proyek Tesis Magister Lintang Amarul Fatah (NIM 25.01.85.0020), Universitas Stikubank Semarang.
    </div>
</body>
</html>

