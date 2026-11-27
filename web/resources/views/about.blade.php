@extends('layouts.app')

@section('title', 'Metodologi, 21 Indikator BRFSS & Keterbatasan Model')

@section('content')
<div class="space-y-8">
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold uppercase tracking-wider text-teal-700">Dokumentasi Transparansi Model</span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Spesifikasi Model, Codebook 21 Fitur BRFSS, & Keterbatasan Ilmiah</h1>
        <p class="text-sm text-slate-600 mt-2 max-w-3xl">
            Halaman ini menjelaskan sumber data, definisi operasional ke-21 indikator kesehatan yang digunakan oleh model XGBoost + TreeSHAP, serta batasan interpretasi yang wajib dipahami oleh pengguna maupun tenaga medis.
        </p>

        @if($activeModel)
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-4 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                <div>
                    <span class="text-slate-500 block">Versi Model Aktif</span>
                    <strong class="text-slate-900 text-sm font-mono">{{ $activeModel->version_tag }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Skenario & Teknik Pemenang</span>
                    <strong class="text-teal-700 text-sm">{{ $activeModel->name }} ({{ $activeModel->technique }})</strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Hash Dataset (SHA-256)</span>
                    <strong class="text-slate-800 font-mono">{{ substr($activeModel->dataset_hash ?? '-', 0, 16) }}...</strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Performa Test (ROC-AUC OvR)</span>
                    <strong class="text-slate-900 text-sm">{{ number_format((float) ($activeModel->metrics['test_metrics']['roc_auc_ovr_macro'] ?? 0), 4) }}</strong>
                </div>
            </div>
        @endif
    </div>

    <!-- Limitations & Ethical Guardrails -->
    <section aria-labelledby="limitations-heading" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs space-y-3">
            <h2 id="limitations-heading" class="text-lg font-bold text-slate-900">1. Keterbatasan Dataset & Populasi (BRFSS 2015)</h2>
            <ul class="list-disc list-inside text-xs sm:text-sm text-slate-600 space-y-2 leading-relaxed">
                <li><strong>Populasi Survei AS:</strong> Dataset berasal dari <em>Behavioral Risk Factor Surveillance System (BRFSS)</em> CDC tahun 2015 di Amerika Serikat (Teboul, 2021), bukan sampel populasi klinis di Indonesia.</li>
                <li><strong>Ketidakseimbangan Kelas Ekstrem:</strong> Kelas <em>Prediabetes</em> hanya mencakup sekitar 1,8% dari total responden, sehingga konfirmasi laboratorium tetap wajib dilakukan.</li>
                <li><strong>Tanpa Indikator Biokimia:</strong> Dataset ini adalah instrumen skrining gaya hidup & riwayat kesehatan tanpa nilai gula darah puasa atau HbA1c.</li>
            </ul>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs space-y-3">
            <h2 class="text-lg font-bold text-slate-900">2. Prinsip Explainable AI (SHAP) & Guardrail LLM</h2>
            <ul class="list-disc list-inside text-xs sm:text-sm text-slate-600 space-y-2 leading-relaxed">
                <li><strong>Korelasi Bukan Kausalitas:</strong> Nilai SHAP menjelaskan bagaimana fitur memengaruhi skor prediksi <em>model</em>, bukan membuktikan hubungan sebab-akibat biologis langsung pada tubuh pasien.</li>
                <li><strong>LLM Tidak Mengklasifikasi:</strong> Label status, probabilitas, dan nilai SHAP sepenuhnya dihitung oleh model Python. LLM hanya menerjemahkan data JSON terstruktur menjadi narasi bahasa alami.</li>
                <li><strong>Privasi Data Kesehatan:</strong> Tidak ada nama, email, atau identitas pribadi yang dikirim ke layanan LLM pihak ketiga.</li>
            </ul>
        </div>
    </section>

    <!-- Codebook Table -->
    <section aria-labelledby="codebook-caption" class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-bold text-slate-900">Codebook 21 Fitur Masukan Model (`Diabetes_012`)</h2>
            <p class="text-xs text-slate-500 mt-0.5">Sumber tunggal kebenaran (<em>Single Source of Truth</em>) dari `feature_schema.json` yang digunakan bersama oleh Laravel dan FastAPI.</p>
        </div>
        <div tabindex="0" role="region" aria-labelledby="codebook-caption" class="table-wrapper">
            <table class="text-left text-xs sm:text-sm">
                <caption id="codebook-caption" class="visually-hidden">Daftar 21 fitur indikator kesehatan BRFSS 2015 beserta tipe dan rentang nilainya</caption>
                <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th scope="col" class="py-3 px-4">No</th>
                        <th scope="col" class="py-3 px-4">Kode Fitur</th>
                        <th scope="col" class="py-3 px-4">Label Indikator (Bahasa Indonesia)</th>
                        <th scope="col" class="py-3 px-4">Tipe</th>
                        <th scope="col" class="py-3 px-4">Rentang Valid</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @php $no = 1; @endphp
                    @foreach($specs as $code => $spec)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4 text-slate-500">{{ $no++ }}</td>
                            <th scope="row" class="py-3 px-4 font-mono font-semibold text-teal-800">{{ $code }}</th>
                            <td class="py-3 px-4 font-medium text-slate-900">{{ $spec['label_id'] ?? $code }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-xs">{{ $spec['type'] ?? 'numeric' }}</span>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600">{{ $spec['min'] ?? 0 }} – {{ $spec['max'] ?? 1 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

