@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')

@section('title', 'Metodologi & Keterbatasan Model')
@section('page_title', 'Metodologi & Keterbatasan')

@section('content')
<div class="space-y-6">
    <!-- Overview Card -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/90 shadow-2xs space-y-6">
        <div class="space-y-1.5">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200/80">
                Transparansi Model &amp; Dokumentasi Ilmiah
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Spesifikasi Model, Codebook 21 Fitur BRFSS &amp; Keterbatasan</h1>
            <p class="text-sm text-slate-600 max-w-3xl leading-relaxed">
                Halaman ini memaparkan sumber data, definisi operasional ke-21 indikator kesehatan yang digunakan oleh model XGBoost + TreeSHAP, serta batasan interpretasi klinis bagi pengguna maupun tenaga medis.
            </p>
        </div>

        @if($activeModel)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                    <span class="text-xs font-medium text-slate-500 block">Versi Model Aktif</span>
                    <strong class="text-slate-900 text-sm font-mono font-bold mt-1 block">{{ $activeModel->version_tag }}</strong>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                    <span class="text-xs font-medium text-slate-500 block">Skenario Pemenang</span>
                    <strong class="text-teal-700 text-sm font-bold mt-1 block">{{ $activeModel->name }}</strong>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                    <span class="text-xs font-medium text-slate-500 block">Hash Dataset (SHA-256)</span>
                    <strong class="text-slate-800 text-sm font-mono font-semibold mt-1 block truncate">{{ substr($activeModel->dataset_hash ?? '-', 0, 16) }}…</strong>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                    <span class="text-xs font-medium text-slate-500 block">Performa Test (ROC-AUC OvR)</span>
                    <strong class="text-slate-900 text-sm font-mono font-bold mt-1 block">{{ number_format((float) ($activeModel->metrics['test_metrics']['roc_auc_ovr_macro'] ?? 0) * 100, 2, ',', '.') }}%</strong>
                </div>
            </div>
        @endif
    </div>

    <!-- Limitations & Ethical Guardrails -->
    <section aria-labelledby="limitations-heading" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-3">
            <h2 id="limitations-heading" class="text-base font-bold text-slate-900">1. Keterbatasan Dataset &amp; Populasi (BRFSS 2015)</h2>
            <ul class="list-disc list-inside text-xs sm:text-sm text-slate-600 space-y-2 leading-relaxed">
                <li><strong>Populasi Survei AS:</strong> Dataset berasal dari <em>Behavioral Risk Factor Surveillance System (BRFSS)</em> CDC tahun 2015 di Amerika Serikat (Teboul, 2021), bukan sampel populasi klinis Indonesia.</li>
                <li><strong>Ketidakseimbangan Kelas Ekstrem:</strong> Kelas <em>Prediabetes</em> hanya mencakup ~1,8% responden, sehingga konfirmasi laboratorium (GDP, TTGO, HbA1c) tetap wajib.</li>
                <li><strong>Tanpa Indikator Biokimia:</strong> Instrumen ini merupakan skrining berbasis gaya hidup &amp; riwayat kesehatan mandiri.</li>
            </ul>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-3">
            <h2 class="text-base font-bold text-slate-900">2. Prinsip Explainable AI (TreeSHAP) &amp; Guardrail LLM</h2>
            <ul class="list-disc list-inside text-xs sm:text-sm text-slate-600 space-y-2 leading-relaxed">
                <li><strong>Korelasi Bukan Kausalitas:</strong> Nilai SHAP menjelaskan pengaruh fitur terhadap skor prediksi <em>model</em>, bukan bukti kausalitas biologis.</li>
                <li><strong>LLM Tidak Mengklasifikasi:</strong> Prediksi dan kontribusi SHAP sepenuhnya dihitung oleh model Python; LLM hanya menyusun narasi tervalidasi dari JSON fakta.</li>
                <li><strong>Privasi Data Kesehatan (UU PDP):</strong> Tidak ada nama asli, email, atau NIK yang dikirim ke API LLM pihak ketiga.</li>
            </ul>
        </div>
    </section>

    <!-- Codebook Table -->
    <section aria-labelledby="codebook-caption" class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="p-6 border-b border-slate-200/80">
            <h2 class="text-base font-bold text-slate-900">Codebook 21 Fitur Masukan Model (<code class="font-mono text-teal-700">Diabetes_012</code>)</h2>
            <p class="text-xs text-slate-500 mt-0.5">Sumber tunggal kebenaran (<em>Single Source of Truth</em>) dari <code class="font-mono">feature_schema.json</code> yang disinkronkan antara Laravel dan FastAPI.</p>
        </div>
        <div tabindex="0" role="region" aria-labelledby="codebook-caption" class="table-wrapper">
            <table class="text-left text-xs sm:text-sm">
                <caption id="codebook-caption" class="visually-hidden">Daftar 21 fitur indikator kesehatan BRFSS 2015 beserta tipe dan rentang nilainya</caption>
                <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                    <tr>
                        <th scope="col" class="py-3 px-4 w-14">No</th>
                        <th scope="col" class="py-3 px-4">Kode Fitur</th>
                        <th scope="col" class="py-3 px-4">Label Indikator (Bahasa Indonesia)</th>
                        <th scope="col" class="py-3 px-4">Tipe</th>
                        <th scope="col" class="py-3 px-4">Rentang Valid</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70">
                    @php $no = 1; @endphp
                    @foreach($specs as $code => $spec)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4 font-mono text-slate-500">{{ $no++ }}</td>
                            <th scope="row" class="py-3 px-4 font-mono font-semibold text-teal-800">{{ $code }}</th>
                            <td class="py-3 px-4 font-medium text-slate-900">{{ $spec['label_id'] ?? $code }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-mono text-xs">{{ $spec['type'] ?? 'numeric' }}</span>
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
