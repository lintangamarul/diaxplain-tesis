@extends('layouts.app')

@section('title', 'Bandingkan Dua Hasil Skrining Antar-Waktu')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs">
        <h1 class="text-2xl font-extrabold text-slate-900">Perbandingan Skrining Antar-Waktu</h1>
        <p class="text-xs sm:text-sm text-slate-600 mt-1">Bandingkan perubahan indikator risiko (seperti BMI, aktivitas fisik, tekanan darah) serta pergeseran probabilitas dan faktor SHAP.</p>

        <form action="{{ route('skrining.compare') }}" method="GET" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end text-xs">
            <div>
                <label for="left_id" class="block font-bold text-slate-700 mb-1">Skrining Pertama (Kiri)</label>
                <select id="left_id" name="left_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 font-medium">
                    @foreach($allScreenings as $s)
                        <option value="{{ $s->id }}" {{ ($left?->id === $s->id) ? 'selected' : '' }}>
                            {{ $s->patient_label ?: ('SCR-' . strtoupper(substr($s->id, 0, 8))) }} — {{ $s->created_at?->format('d M Y H:i') }} ({{ $s->prediction?->label }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="right_id" class="block font-bold text-slate-700 mb-1">Skrining Pembanding (Kanan)</label>
                <select id="right_id" name="right_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 font-medium">
                    @foreach($allScreenings as $s)
                        <option value="{{ $s->id }}" {{ ($right?->id === $s->id) ? 'selected' : '' }}>
                            {{ $s->patient_label ?: ('SCR-' . strtoupper(substr($s->id, 0, 8))) }} — {{ $s->created_at?->format('d M Y H:i') }} ({{ $s->prediction?->label }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold tap-target">
                Bandingkan Sekarang
            </button>
        </form>
    </div>

    @if($left && $right)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach([$left, $right] as $idx => $item)
                @php $p = $item->prediction; @endphp
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <span class="text-xs font-mono text-slate-500">{{ $item->patient_label ?: ('SCR-' . strtoupper(substr($item->id, 0, 8))) }}</span>
                            <h2 class="text-lg font-extrabold text-slate-900">{{ $p?->label_id ?? $p?->label }} ({{ $p?->label }})</h2>
                        </div>
                        <span class="text-xs text-slate-500">{{ $item->created_at?->format('d M Y, H:i') }}</span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-center text-xs">
                        @foreach(['No Diabetes' => 'Tidak Diabetes', 'Prediabetes' => 'Prediabetes', 'Diabetes' => 'Diabetes'] as $k => $lbl)
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                                <span class="block text-slate-500">{{ $lbl }}</span>
                                <strong class="text-sm font-extrabold text-slate-900">{{ number_format(((float) ($p?->probabilities[$k] ?? 0)) * 100, 1, ',', '.') }}%</strong>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-1.5 text-xs">
                        <h3 class="font-bold text-slate-800">Top-3 Kontribusi SHAP Lokal:</h3>
                        @foreach(array_slice($p?->shap_local['top'] ?? [], 0, 3) as $sh)
                            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between">
                                <span><strong>{{ $sh['label_id'] }}</strong> ({{ $sh['display'] }})</span>
                                <span class="font-mono font-bold {{ ($sh['direction'] ?? 'naik') === 'naik' ? 'text-rose-700' : 'text-teal-700' }}">
                                    {{ ($sh['direction'] ?? 'naik') === 'naik' ? '▲ +' : '▼ ' }}{{ number_format((float) $sh['shap'], 3) }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-2">
                        <a href="{{ route('skrining.show', $item->id) }}" class="text-xs font-bold text-teal-700 hover:underline">Buka Laporan Lengkap →</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

