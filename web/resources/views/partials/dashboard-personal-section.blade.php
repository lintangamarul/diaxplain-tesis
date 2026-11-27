<!-- Personal / Clinical Screening KPI Grid (4 Columns with Soft Icon Accents) -->
<section aria-labelledby="my-stats-heading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <h2 id="my-stats-heading" class="visually-hidden">Ringkasan Riwayat Skrining</h2>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-2xs flex items-start justify-between gap-3">
        <div>
            <span class="text-xs font-semibold text-slate-500">Total Skrining</span>
            <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $myTotalScreenings }}</div>
            <p class="text-[11px] text-slate-500 mt-0.5">Tersimpan pada akun ini</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
            <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-2xs flex items-start justify-between gap-3">
        <div>
            <span class="text-xs font-semibold text-emerald-700">Tidak Diabetes (0)</span>
            <div class="text-2xl font-extrabold text-emerald-700 mt-1 font-mono">{{ $classDistribution['No Diabetes'] ?? 0 }}</div>
            <p class="text-[11px] text-slate-500 mt-0.5">Profil risiko rendah</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200/70 flex items-center justify-center shrink-0">
            <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-2xs flex items-start justify-between gap-3">
        <div>
            <span class="text-xs font-semibold text-amber-700">Prediabetes (1)</span>
            <div class="text-2xl font-extrabold text-amber-700 mt-1 font-mono">{{ $classDistribution['Prediabetes'] ?? 0 }}</div>
            <p class="text-[11px] text-slate-500 mt-0.5">Perlu kewaspadaan</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-200/70 flex items-center justify-center shrink-0">
            <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/90 shadow-2xs flex items-start justify-between gap-3">
        <div>
            <span class="text-xs font-semibold text-rose-700">Diabetes (2)</span>
            <div class="text-2xl font-extrabold text-rose-700 mt-1 font-mono">{{ $classDistribution['Diabetes'] ?? 0 }}</div>
            <p class="text-[11px] text-slate-500 mt-0.5">Disarankan cek klinis</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 border border-rose-200/70 flex items-center justify-center shrink-0">
            <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
    </div>
</section>

<!-- Recent Screenings Table -->
<section aria-labelledby="recent-screenings-caption" class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200/80 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Riwayat Skrining Terbaru</h2>
            <p class="text-xs text-slate-500 mt-0.5">Klik <strong>Detail SPK</strong> untuk melihat grafik kontribusi SHAP lokal, narasi LLM, dan unduh PDF.</p>
        </div>
        <a href="{{ route('skrining.index') }}" class="text-xs font-bold text-teal-700 hover:underline whitespace-nowrap">Lihat Semua →</a>
    </div>

    @if($myScreenings->isEmpty())
        <div class="p-10 text-center space-y-3">
            <p class="text-sm text-slate-600">Belum ada riwayat skrining pada akun ini.</p>
            <a href="{{ route('skrining.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-semibold text-xs transition">
                + Buat Skrining Pertama Anda
            </a>
        </div>
    @else
        <div tabindex="0" role="region" aria-labelledby="recent-screenings-caption" class="table-wrapper">
            <table class="text-left text-xs sm:text-sm">
                <caption id="recent-screenings-caption" class="visually-hidden">Daftar riwayat skrining terbaru beserta hasil klasifikasi dan probabilitasnya</caption>
                <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                    <tr>
                        <th scope="col" class="py-3 px-4">ID / Pseudonim</th>
                        <th scope="col" class="py-3 px-4">Waktu Skrining</th>
                        <th scope="col" class="py-3 px-4">BMI &amp; Usia</th>
                        <th scope="col" class="py-3 px-4">Status Klasifikasi</th>
                        <th scope="col" class="py-3 px-4">Keyakinan</th>
                        <th scope="col" class="py-3 px-4">Narasi AI</th>
                        <th scope="col" class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70">
                    @foreach($myScreenings as $scr)
                        @php
                            $pred = $scr->prediction;
                            $interp = $scr->latestInterpretation;
                            $badgeClass = match($pred?->label_code) {
                                0 => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                1 => 'bg-amber-50 text-amber-900 border-amber-200',
                                2 => 'bg-rose-50 text-rose-800 border-rose-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <th scope="row" class="py-3.5 px-4 font-mono text-xs font-bold text-slate-900 whitespace-nowrap">
                                {{ $scr->patient_label ?: ('SCR-' . strtoupper(substr($scr->id, 0, 8))) }}
                            </th>
                            <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">{{ $scr->created_at?->format('d M Y, H:i') }}</td>
                            <td class="py-3.5 px-4 text-slate-700 whitespace-nowrap">
                                <span class="font-mono font-semibold">{{ $scr->input['BMI'] ?? '-' }}</span> · Kat. {{ $scr->input['Age'] ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($pred)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                                        {{ $pred->label_id ?? $pred->label }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 capitalize text-slate-600 font-medium whitespace-nowrap">{{ $pred?->confidence_note ?? '-' }}</td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($interp && $interp->status === 'done')
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $interp->used_fallback ? 'text-slate-600' : 'text-teal-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $interp->used_fallback ? 'bg-slate-400' : 'bg-teal-600' }}"></span>
                                        {{ $interp->used_fallback ? 'Templat Tervalidasi' : 'Live LLM API' }}
                                    </span>
                                @else
                                    <span class="text-xs text-amber-700 font-medium">Menyusun…</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                <a href="{{ route('skrining.show', $scr->id) }}" class="inline-flex px-3 py-1.5 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-800 font-semibold text-xs transition">
                                    Detail SPK
                                </a>
                                <a href="{{ route('skrining.pdf', $scr->id) }}" class="inline-flex px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                                    PDF
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
