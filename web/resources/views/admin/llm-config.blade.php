@extends('layouts.app')

@section('title', 'Konfigurasi API Key Layanan AI (LLM Pihak Ketiga)')
@section('page_title', 'Konfigurasi AI / LLM')

@section('content')
@php
    $mlOk = ($mlHealth['status'] ?? '') === 'ok';
@endphp

<div class="space-y-6" x-data="{
    provider: '{{ old('provider', $provider) }}',
    baseUrl: '{{ old('base_url', $baseUrl) }}',
    modelName: '{{ old('model', $modelName) }}',
    applyPreset(prov, url, mod) {
        this.provider = prov;
        this.baseUrl = url;
        this.modelName = mod;
    }
}">
    <!-- Header Banner -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200/80">
                    Integrasi API Pihak Ketiga (Tanpa Ollama)
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-semibold border {{ $mlOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $mlOk ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    ML Service: {{ $mlOk ? 'Terhubung' : 'Mode Fallback Lokal' }}
                </span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900">Konfigurasi API Key Layanan AI (LLM)</h1>
            <p class="text-xs sm:text-sm text-slate-600 max-w-3xl">
                Hubungkan OpenAI, Google Gemini, Groq, OpenRouter, DeepSeek, atau Anthropic Claude untuk narasi interpretasi SHAP. Tanpa API Key, sistem otomatis memakai <em>Template Fallback Deterministik</em>.
            </p>
        </div>
        <div class="shrink-0">
            @if($hasApiKey)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-mono font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    ✓ Key Aktif ({{ $maskedKey }})
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                    Belum Ada Key · Mode Templat
                </span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Main Configuration Form -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/90 p-6 sm:p-8 shadow-2xs space-y-6">
            <div>
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2.5">Preset Cepat Penyedia API Pihak Ketiga</span>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <button type="button" @click="applyPreset('openai_compatible', 'https://api.openai.com/v1', 'gpt-4o-mini')" class="px-3 py-2.5 rounded-xl border border-slate-200 hover:border-teal-600 hover:bg-teal-50/40 text-left transition">
                        <div class="text-xs font-bold text-slate-900">OpenAI Official</div>
                        <div class="text-[11px] font-mono text-slate-500 truncate">gpt-4o-mini</div>
                    </button>
                    <button type="button" @click="applyPreset('gemini', 'https://generativelanguage.googleapis.com/v1beta', 'gemini-2.5-flash')" class="px-3 py-2.5 rounded-xl border border-slate-200 hover:border-teal-600 hover:bg-teal-50/40 text-left transition">
                        <div class="text-xs font-bold text-slate-900">Google Gemini</div>
                        <div class="text-[11px] font-mono text-slate-500 truncate">gemini-2.5-flash</div>
                    </button>
                    <button type="button" @click="applyPreset('openai_compatible', 'https://api.groq.com/openai/v1', 'llama-3.3-70b-versatile')" class="px-3 py-2.5 rounded-xl border border-slate-200 hover:border-teal-600 hover:bg-teal-50/40 text-left transition">
                        <div class="text-xs font-bold text-slate-900">Groq Cloud</div>
                        <div class="text-[11px] font-mono text-slate-500 truncate">llama-3.3-70b</div>
                    </button>
                    <button type="button" @click="applyPreset('openai_compatible', 'https://openrouter.ai/api/v1', 'openai/gpt-4o-mini')" class="px-3 py-2.5 rounded-xl border border-slate-200 hover:border-teal-600 hover:bg-teal-50/40 text-left transition">
                        <div class="text-xs font-bold text-slate-900">OpenRouter</div>
                        <div class="text-[11px] font-mono text-slate-500 truncate">openai/gpt-4o-mini</div>
                    </button>
                    <button type="button" @click="applyPreset('openai_compatible', 'https://api.deepseek.com/v1', 'deepseek-chat')" class="px-3 py-2.5 rounded-xl border border-slate-200 hover:border-teal-600 hover:bg-teal-50/40 text-left transition">
                        <div class="text-xs font-bold text-slate-900">DeepSeek API</div>
                        <div class="text-[11px] font-mono text-slate-500 truncate">deepseek-chat</div>
                    </button>
                    <button type="button" @click="applyPreset('anthropic', 'https://api.anthropic.com', 'claude-3-5-haiku-latest')" class="px-3 py-2.5 rounded-xl border border-slate-200 hover:border-teal-600 hover:bg-teal-50/40 text-left transition">
                        <div class="text-xs font-bold text-slate-900">Anthropic Claude</div>
                        <div class="text-[11px] font-mono text-slate-500 truncate">claude-3-5-haiku</div>
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.llm.update') }}" class="space-y-5 pt-4 border-t border-slate-200/80">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="provider" class="block text-xs font-bold text-slate-700 mb-1">Adapter Protokol API</label>
                        <select id="provider" name="provider" x-model="provider" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-600">
                            <option value="openai_compatible">OpenAI-Compatible (OpenAI / Groq / OpenRouter / DeepSeek)</option>
                            <option value="gemini">Google Gemini REST API</option>
                            <option value="anthropic">Anthropic Messages API (Claude)</option>
                            <option value="template_fallback">Template Fallback Deterministik (Tanpa API)</option>
                        </select>
                    </div>

                    <div>
                        <label for="model" class="block text-xs font-bold text-slate-700 mb-1">Nama Model LLM</label>
                        <input type="text" id="model" name="model" x-model="modelName" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono focus:border-teal-600" placeholder="gpt-4o-mini / gemini-2.5-flash">
                    </div>
                </div>

                <div>
                    <label for="api_key" class="block text-xs font-bold text-slate-700 mb-1">
                        API Key Pihak Ketiga
                        @if($hasApiKey)
                            <span class="text-emerald-700 font-normal">(Kosongkan jika tetap memakai <code class="font-mono">{{ $maskedKey }}</code>)</span>
                        @endif
                    </label>
                    <input type="password" id="api_key" name="api_key" autocomplete="off" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono focus:border-teal-600" placeholder="Masukkan API Key (mis. sk-... / AIza... / gsk_...)">
                    @if($hasApiKey)
                        <label class="inline-flex items-center gap-2 mt-2 text-xs text-rose-700 cursor-pointer">
                            <input type="checkbox" name="clear_api_key" value="1" class="rounded border-slate-300 accent-rose-600">
                            <span>Hapus API Key yang tersimpan dan kembali ke mode Template Fallback</span>
                        </label>
                    @endif
                </div>

                <div>
                    <label for="base_url" class="block text-xs font-bold text-slate-700 mb-1">Endpoint Base URL</label>
                    <input type="url" id="base_url" name="base_url" x-model="baseUrl" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono focus:border-teal-600" placeholder="https://api.openai.com/v1">
                    <p class="text-[11px] text-slate-500 mt-1">Digunakan untuk penyedia OpenAI-Compatible. Untuk Gemini/Anthropic otomatis menyesuaikan.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="temperature" class="block text-xs font-bold text-slate-700 mb-1">Temperature (0,0–1,0)</label>
                        <input type="number" step="0.05" min="0" max="1.5" id="temperature" name="temperature" value="{{ old('temperature', $temperature) }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono">
                    </div>
                    <div>
                        <label for="shap_top_k" class="block text-xs font-bold text-slate-700 mb-1">Fitur Top-K SHAP</label>
                        <input type="number" min="3" max="15" id="shap_top_k" name="shap_top_k" value="{{ old('shap_top_k', $topK) }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono">
                    </div>
                    <div>
                        <label for="prompt_version" class="block text-xs font-bold text-slate-700 mb-1">Versi Prompt</label>
                        <input type="text" id="prompt_version" name="prompt_version" value="{{ old('prompt_version', $promptVersion) }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-mono">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="test_connection" value="1" checked class="rounded border-slate-300 accent-teal-700">
                        <span>Uji koneksi langsung saat menyimpan</span>
                    </label>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white text-xs sm:text-sm font-semibold shadow-xs transition tap-target">
                        Simpan &amp; Sinkronkan ke ML Service
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Sidebar: Guardrail & Env Info -->
        <div class="lg:col-span-4 space-y-5">
            <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs space-y-3">
                <h2 class="text-sm font-extrabold text-slate-900">Arsitektur Guardrail 7-Lapis</h2>
                <ol class="text-xs text-slate-600 space-y-1.5 list-decimal list-inside leading-relaxed">
                    <li>Validasi struktur JSON ketat (<code class="font-mono">Pydantic</code>).</li>
                    <li>Kecocokan label prediksi 3 kelas.</li>
                    <li>Kecocokan probabilitas &amp; BMI (±0,5).</li>
                    <li>Kepatuhan fitur terhadap Top-K SHAP.</li>
                    <li>Konsistensi arah kontribusi SHAP (+/−).</li>
                    <li>Filter klaim diagnosis &amp; resep obat.</li>
                    <li>Disclaimer medis deterministik wajib.</li>
                </ol>
            </div>

            <div class="bg-slate-900 text-slate-200 rounded-2xl p-5 shadow-2xs space-y-2.5">
                <h2 class="text-xs font-bold uppercase tracking-wider text-teal-400">Alternatif via File .env</h2>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Dapat juga dikonfigurasi melalui <code class="font-mono text-teal-300">ml/.env</code>:
                </p>
                <pre class="text-[11px] font-mono bg-slate-950/90 p-3 rounded-xl overflow-x-auto text-teal-200">LLM_PROVIDER=openai_compatible
LLM_API_KEY=sk-proj-xxxx
LLM_BASE_URL=https://api.openai.com/v1
LLM_MODEL=gpt-4o-mini</pre>
            </div>
        </div>
    </div>

    <!-- Recent LLM Calls Table -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200/80">
            <h2 class="text-base font-bold text-slate-900">Log Panggilan Interpretasi LLM Terbaru</h2>
        </div>
        <div class="table-wrapper">
            <table class="text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th scope="col" class="py-3 px-4">Waktu</th>
                        <th scope="col" class="py-3 px-4">Kasus</th>
                        <th scope="col" class="py-3 px-4">Prediksi</th>
                        <th scope="col" class="py-3 px-4">Provider / Model</th>
                        <th scope="col" class="py-3 px-4">Mode</th>
                        <th scope="col" class="py-3 px-4 text-right">Latensi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70">
                    @forelse($recentLogs as $log)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-4 text-slate-500 font-mono whitespace-nowrap">{{ $log->created_at?->format('d M Y H:i') }}</td>
                            <th scope="row" class="py-3 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                                {{ $log->screening?->patient_label ?: ('SCR-' . strtoupper(substr((string) $log->screening_id, 0, 8))) }}
                            </th>
                            <td class="py-3 px-4 font-semibold text-slate-700 whitespace-nowrap">{{ $log->prediction?->label_id ?? $log->prediction?->label ?? '-' }}</td>
                            <td class="py-3 px-4 font-mono text-slate-600 whitespace-nowrap">{{ $log->provider }} / {{ $log->model_name }}</td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($log->used_fallback)
                                    <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-semibold">Mode Templat</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold">Live LLM API</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-slate-700 whitespace-nowrap">{{ $log->latency_ms ?? 0 }} ms</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">Belum ada log interpretasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
