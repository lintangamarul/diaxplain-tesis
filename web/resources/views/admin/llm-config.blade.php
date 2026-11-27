@extends('layouts.app')

@section('title', 'Konfigurasi API Key Layanan AI (LLM Pihak Ketiga)')

@section('content')
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
    <!-- Admin Sub-navigation -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white rounded-2xl border border-slate-200/80 px-5 py-3 shadow-sm">
        <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
            <a href="{{ route('admin.experiments') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Dasbor Eksperimen (RM1–RM6)</a>
            <a href="{{ route('admin.llm.config') }}" class="px-3 py-1.5 rounded-xl bg-teal-50 text-teal-800 border border-teal-200">API Key Layanan AI (LLM)</a>
            <a href="{{ route('admin.models') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Registri Model</a>
            <a href="{{ route('admin.users') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Manajemen Pengguna</a>
            <a href="{{ route('admin.audit') }}" class="px-3 py-1.5 rounded-xl text-slate-600 hover:bg-slate-100">Log Audit</a>
        </div>
        <div class="flex items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-semibold {{ ($mlHealth['status'] ?? '') === 'ok' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                <span class="w-2 h-2 rounded-full {{ ($mlHealth['status'] ?? '') === 'ok' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                ML Service: {{ ($mlHealth['status'] ?? '') === 'ok' ? 'Terhubung' : 'Mode Lokal/Fallback' }}
            </span>
        </div>
    </div>

    <!-- Header Banner -->
    <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-teal-950 text-white rounded-3xl p-6 sm:p-8 shadow-md">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-teal-500/20 text-teal-200 border border-teal-400/30 mb-3">
                Integrasi AI Pihak Ketiga (Tanpa Ollama Lokal)
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Ruang Konfigurasi API Key Layanan AI (LLM)</h1>
            <p class="text-sm text-slate-300 mt-2 leading-relaxed">
                Masukkan <strong>API Key layanan AI pihak ketiga</strong> Anda (OpenAI, Google Gemini, Groq, OpenRouter, DeepSeek, atau Anthropic Claude) di bawah ini. Sistem secara otomatis memvalidasi fakta SHAP 7-lapis dan menyediakan <em>Template Fallback Deterministik</em> apabila API Key belum diisi atau kuota habis.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Main Configuration Form -->
        <div class="lg:col-span-8 bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-5 mb-6 border-b border-slate-200/80">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">Pengaturan Kredensial & Parameter LLM</h2>
                    <p class="text-xs text-slate-500">Pilih preset penyedia layanan di bawah ini untuk mengisi Base URL dan nama model secara cepat.</p>
                </div>
                @if($hasApiKey)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        ✓ API Key Tersimpan ({{ $maskedKey }})
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                        Belum Ada API Key (Aktif: Template Fallback)
                    </span>
                @endif
            </div>

            <!-- Quick Presets -->
            <div class="mb-6">
                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2.5">Preset Cepat Penyedia API Pihak Ketiga</span>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <button type="button" @click="applyPreset('openai_compatible', 'https://api.openai.com/v1', 'gpt-4o-mini')" class="px-3 py-2 rounded-xl border border-slate-200 hover:border-teal-500 hover:bg-teal-50/40 text-left transition">
                        <div class="text-xs font-bold text-slate-900">OpenAI Official</div>
                        <div class="text-[11px] text-slate-500 truncate">gpt-4o-mini</div>
                    </button>
                    <button type="button" @click="applyPreset('gemini', 'https://generativelanguage.googleapis.com/v1beta', 'gemini-2.5-flash')" class="px-3 py-2 rounded-xl border border-slate-200 hover:border-teal-500 hover:bg-teal-50/40 text-left transition">
                        <div class="text-xs font-bold text-slate-900">Google Gemini API</div>
                        <div class="text-[11px] text-slate-500 truncate">gemini-2.5-flash</div>
                    </button>
                    <button type="button" @click="applyPreset('openai_compatible', 'https://api.groq.com/openai/v1', 'llama-3.3-70b-versatile')" class="px-3 py-2 rounded-xl border border-slate-200 hover:border-teal-500 hover:bg-teal-50/40 text-left transition">
                        <div class="text-xs font-bold text-slate-900">Groq Cloud</div>
                        <div class="text-[11px] text-slate-500 truncate">llama-3.3-70b-versatile</div>
                    </button>
                    <button type="button" @click="applyPreset('openai_compatible', 'https://openrouter.ai/api/v1', 'openai/gpt-4o-mini')" class="px-3 py-2 rounded-xl border border-slate-200 hover:border-teal-500 hover:bg-teal-50/40 text-left transition">
                        <div class="text-xs font-bold text-slate-900">OpenRouter</div>
                        <div class="text-[11px] text-slate-500 truncate">openai/gpt-4o-mini</div>
                    </button>
                    <button type="button" @click="applyPreset('openai_compatible', 'https://api.deepseek.com/v1', 'deepseek-chat')" class="px-3 py-2 rounded-xl border border-slate-200 hover:border-teal-500 hover:bg-teal-50/40 text-left transition">
                        <div class="text-xs font-bold text-slate-900">DeepSeek API</div>
                        <div class="text-[11px] text-slate-500 truncate">deepseek-chat</div>
                    </button>
                    <button type="button" @click="applyPreset('anthropic', 'https://api.anthropic.com', 'claude-3-5-haiku-latest')" class="px-3 py-2 rounded-xl border border-slate-200 hover:border-teal-500 hover:bg-teal-50/40 text-left transition">
                        <div class="text-xs font-bold text-slate-900">Anthropic Claude</div>
                        <div class="text-[11px] text-slate-500 truncate">claude-3-5-haiku-latest</div>
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.llm.update') }}" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="provider" class="block text-xs font-bold text-slate-700 mb-1">Adapter Protokol API</label>
                        <select id="provider" name="provider" x-model="provider" class="w-full rounded-xl border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                            <option value="openai_compatible">OpenAI-Compatible (OpenAI / Groq / OpenRouter / DeepSeek)</option>
                            <option value="gemini">Google Gemini REST API (generativelanguage)</option>
                            <option value="anthropic">Anthropic Messages API (Claude)</option>
                            <option value="template_fallback">Template Fallback Deterministik (Tanpa Panggilan API)</option>
                        </select>
                    </div>

                    <div>
                        <label for="model" class="block text-xs font-bold text-slate-700 mb-1">Nama Model LLM</label>
                        <input type="text" id="model" name="model" x-model="modelName" required class="w-full rounded-xl border-slate-300 text-sm font-mono focus:border-teal-600 focus:ring-teal-600" placeholder="gpt-4o-mini / gemini-2.5-flash">
                    </div>
                </div>

                <div>
                    <label for="api_key" class="block text-xs font-bold text-slate-700 mb-1">
                        API Key Pihak Ketiga
                        @if($hasApiKey)
                            <span class="text-emerald-700 font-normal">(Kosongkan jika tidak ingin mengubah key `{{ $maskedKey }}`)</span>
                        @endif
                    </label>
                    <input type="password" id="api_key" name="api_key" autocomplete="off" class="w-full rounded-xl border-slate-300 text-sm font-mono focus:border-teal-600 focus:ring-teal-600" placeholder="Masukkan API Key Anda di sini (mis. sk-... / AIza... / gsk_...)">
                    @if($hasApiKey)
                        <label class="inline-flex items-center gap-2 mt-2 text-xs text-rose-700 cursor-pointer">
                            <input type="checkbox" name="clear_api_key" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            Hapus API Key yang tersimpan dan kembali ke mode Template Fallback
                        </label>
                    @endif
                </div>

                <div>
                    <label for="base_url" class="block text-xs font-bold text-slate-700 mb-1">Endpoint Base URL</label>
                    <input type="url" id="base_url" name="base_url" x-model="baseUrl" class="w-full rounded-xl border-slate-300 text-sm font-mono focus:border-teal-600 focus:ring-teal-600" placeholder="https://api.openai.com/v1">
                    <p class="text-[11px] text-slate-500 mt-1">Digunakan untuk penyedia OpenAI-Compatible. Untuk Gemini/Anthropic akan otomatis menyesuaikan jika dikosongkan.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                    <div>
                        <label for="temperature" class="block text-xs font-bold text-slate-700 mb-1">Temperature (0,0–1,0)</label>
                        <input type="number" step="0.05" min="0" max="1.5" id="temperature" name="temperature" value="{{ old('temperature', $temperature) }}" required class="w-full rounded-xl border-slate-300 text-sm font-mono focus:border-teal-600 focus:ring-teal-600">
                        <p class="text-[11px] text-slate-500 mt-1">Disarankan 0.2 agar faktual.</p>
                    </div>
                    <div>
                        <label for="shap_top_k" class="block text-xs font-bold text-slate-700 mb-1">Jumlah Fitur Top-K SHAP</label>
                        <input type="number" min="3" max="15" id="shap_top_k" name="shap_top_k" value="{{ old('shap_top_k', $topK) }}" required class="w-full rounded-xl border-slate-300 text-sm font-mono focus:border-teal-600 focus:ring-teal-600">
                        <p class="text-[11px] text-slate-500 mt-1">Default: 6 fitur teratas.</p>
                    </div>
                    <div>
                        <label for="prompt_version" class="block text-xs font-bold text-slate-700 mb-1">Versi Prompt</label>
                        <input type="text" id="prompt_version" name="prompt_version" value="{{ old('prompt_version', $promptVersion) }}" required class="w-full rounded-xl border-slate-300 text-sm font-mono focus:border-teal-600 focus:ring-teal-600">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="test_connection" value="1" checked class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        Uji koneksi langsung ke layanan AI saat menyimpan
                    </label>
                    <button type="submit" class="px-6 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-extrabold shadow-sm transition">
                        Simpan Konfigurasi & Sinkronkan ke ML Service
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Sidebar: Security & Architecture Info -->
        <div class="lg:col-span-4 space-y-5">
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-3">
                <h3 class="text-sm font-extrabold text-slate-900">Arsitektur Guardrail 7-Lapis</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Setiap respons dari API pihak ketiga wajib melewati validator deterministik sebelum ditampilkan kepada pengguna:
                </p>
                <ol class="text-xs text-slate-700 space-y-1.5 list-decimal list-inside">
                    <li>Validasi struktur JSON ketat (`Pydantic`).</li>
                    <li>Kecocokan label kelas prediksi (`Non-Diabetes` / `Prediabetes` / `Diabetes`).</li>
                    <li>Kecocokan angka probabilitas & BMI (toleransi ±0,5).</li>
                    <li>Kepatuhan daftar faktor terhadap <strong>Top-K SHAP</strong>.</li>
                    <li>Konsistensi arah kontribusi SHAP (+ mendorong, − menahan).</li>
                    <li>Filter kata terlarang (diagnosis definitif & resep/dosis obat).</li>
                    <li>Kehadiran disclaimer medis wajib.</li>
                </ol>
            </div>

            <div class="bg-slate-900 text-slate-200 rounded-3xl p-6 shadow-sm space-y-2.5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-teal-400">Alternatif via File .env</h3>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Selain melalui halaman ini, Anda juga dapat mengatur API key langsung pada file <code class="text-teal-300">ml/.env</code> atau <code class="text-teal-300">web/.env</code>:
                </p>
                <pre class="text-[11px] font-mono bg-slate-950/90 p-3 rounded-xl overflow-x-auto text-teal-200">LLM_PROVIDER=openai_compatible
LLM_API_KEY=sk-proj-xxxx
LLM_BASE_URL=https://api.openai.com/v1
LLM_MODEL=gpt-4o-mini</pre>
            </div>
        </div>
    </div>

    <!-- Recent LLM Calls Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <h2 class="text-base font-extrabold text-slate-900 mb-4">Log Panggilan Interpretasi LLM Terbaru</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 text-slate-500 uppercase">
                        <th class="py-3 px-3">Waktu</th>
                        <th class="py-3 px-3">Kode Skrining</th>
                        <th class="py-3 px-3">Prediksi</th>
                        <th class="py-3 px-3">Provider / Model</th>
                        <th class="py-3 px-3">Mode</th>
                        <th class="py-3 px-3 text-right">Latensi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/60">
                    @forelse($recentLogs as $log)
                        <tr>
                            <td class="py-3 px-3 text-slate-500">{{ $log->created_at?->format('d M Y H:i') }}</td>
                            <td class="py-3 px-3 font-mono font-bold text-slate-800">{{ $log->screening?->kode_skrining ?? '#' . $log->screening_id }}</td>
                            <td class="py-3 px-3 font-semibold">{{ $log->prediction?->label ?? '-' }}</td>
                            <td class="py-3 px-3 font-mono">{{ $log->provider }} / {{ $log->model_name }}</td>
                            <td class="py-3 px-3">
                                @if($log->used_fallback)
                                    <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-semibold">Template Fallback</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold">Live LLM API</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right font-mono">{{ $log->latency_ms ?? 0 }} ms</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-500">Belum ada log interpretasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
