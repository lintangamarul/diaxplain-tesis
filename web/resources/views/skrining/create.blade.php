@extends('layouts.app')

@section('title', 'Skrining Baru (21 Indikator BRFSS)')
@section('page_title', 'Skrining Baru')

@section('content')
<div
    x-data="{
        step: 1,
        totalSteps: 3,
        consent_data: true,
        patient_label: '{{ old('patient_label', '') }}',
        audience: '{{ old('audience', ($user->isNakes() || $user->isAdmin()) ? 'tenaga_medis' : 'awam') }}',
        age_years: {{ old('age_years', 48) }},
        weight_kg: {{ old('weight_kg', 74) }},
        height_cm: {{ old('height_cm', 165) }},
        form: {
            Sex: {{ old('Sex', 1) }},
            Age: {{ old('Age', 6) }},
            BMI: {{ old('BMI', 27.2) }},
            HighBP: {{ old('HighBP', 0) }},
            HighChol: {{ old('HighChol', 0) }},
            CholCheck: {{ old('CholCheck', 1) }},
            Stroke: {{ old('Stroke', 0) }},
            HeartDiseaseorAttack: {{ old('HeartDiseaseorAttack', 0) }},
            DiffWalk: {{ old('DiffWalk', 0) }},
            GenHlth: {{ old('GenHlth', 2) }},
            MentHlth: {{ old('MentHlth', 1) }},
            PhysHlth: {{ old('PhysHlth', 2) }},
            Smoker: {{ old('Smoker', 0) }},
            PhysActivity: {{ old('PhysActivity', 1) }},
            Fruits: {{ old('Fruits', 1) }},
            Veggies: {{ old('Veggies', 1) }},
            HvyAlcoholConsump: {{ old('HvyAlcoholConsump', 0) }},
            AnyHealthcare: {{ old('AnyHealthcare', 1) }},
            NoDocbcCost: {{ old('NoDocbcCost', 0) }},
            Education: {{ old('Education', 5) }},
            Income: {{ old('Income', 6) }}
        },
        updateAgeCategory() {
            const a = parseInt(this.age_years) || 18;
            if (a < 25) this.form.Age = 1;
            else if (a < 30) this.form.Age = 2;
            else if (a < 35) this.form.Age = 3;
            else if (a < 40) this.form.Age = 4;
            else if (a < 45) this.form.Age = 5;
            else if (a < 50) this.form.Age = 6;
            else if (a < 55) this.form.Age = 7;
            else if (a < 60) this.form.Age = 8;
            else if (a < 65) this.form.Age = 9;
            else if (a < 70) this.form.Age = 10;
            else if (a < 75) this.form.Age = 11;
            else if (a < 80) this.form.Age = 12;
            else this.form.Age = 13;
        },
        updateBmi() {
            const w = parseFloat(this.weight_kg);
            const h = parseFloat(this.height_cm);
            if (w > 0 && h > 50) {
                const m = h / 100.0;
                this.form.BMI = Math.round((w / (m * m)) * 10) / 10;
            }
        },
        bmiCategory() {
            const b = parseFloat(this.form.BMI) || 0;
            if (b < 18.5) return 'Berat Badan Kurang';
            if (b < 23.0) return 'Normal Ideal';
            if (b < 25.0) return 'Normal Batas Atas';
            if (b < 30.0) return 'Overweight';
            if (b < 35.0) return 'Obesitas Kelas I';
            if (b < 40.0) return 'Obesitas Kelas II';
            return 'Obesitas Kelas III';
        },
        applyPreset(type) {
            if (type === 'healthy') {
                this.age_years = 29; this.weight_kg = 60; this.height_cm = 168;
                this.updateAgeCategory(); this.updateBmi();
                Object.assign(this.form, {
                    Sex: 0, HighBP: 0, HighChol: 0, CholCheck: 1, Stroke: 0, HeartDiseaseorAttack: 0,
                    DiffWalk: 0, GenHlth: 1, MentHlth: 0, PhysHlth: 0, Smoker: 0, PhysActivity: 1,
                    Fruits: 1, Veggies: 1, HvyAlcoholConsump: 0, AnyHealthcare: 1, NoDocbcCost: 0,
                    Education: 6, Income: 7
                });
            } else if (type === 'prediabetes') {
                this.age_years = 56; this.weight_kg = 78; this.height_cm = 159;
                this.updateAgeCategory(); this.updateBmi();
                Object.assign(this.form, {
                    Sex: 0, HighBP: 1, HighChol: 1, CholCheck: 1, Stroke: 0, HeartDiseaseorAttack: 0,
                    DiffWalk: 0, GenHlth: 3, MentHlth: 4, PhysHlth: 5, Smoker: 1, PhysActivity: 0,
                    Fruits: 0, Veggies: 1, HvyAlcoholConsump: 0, AnyHealthcare: 1, NoDocbcCost: 0,
                    Education: 5, Income: 5
                });
            } else if (type === 'diabetes') {
                this.age_years = 67; this.weight_kg = 96; this.height_cm = 163;
                this.updateAgeCategory(); this.updateBmi();
                Object.assign(this.form, {
                    Sex: 1, HighBP: 1, HighChol: 1, CholCheck: 1, Stroke: 0, HeartDiseaseorAttack: 1,
                    DiffWalk: 1, GenHlth: 4, MentHlth: 6, PhysHlth: 15, Smoker: 1, PhysActivity: 0,
                    Fruits: 0, Veggies: 0, HvyAlcoholConsump: 0, AnyHealthcare: 1, NoDocbcCost: 0,
                    Education: 4, Income: 4
                });
            }
        }
    }"
    class="max-w-4xl mx-auto space-y-6"
>
    <!-- Compact Header & Preset Case Simulator -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Skrining Status Diabetes (21 Indikator)</h1>
            <p class="text-xs sm:text-sm text-slate-600 mt-0.5">Lengkapi 3 tahap formulir terstruktur berikut untuk analisis XGBoost, kontribusi TreeSHAP, dan narasi LLM.</p>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 text-xs shrink-0">
            <span class="text-slate-500 font-semibold mr-1">Simulasi Kasus:</span>
            <button type="button" @click="applyPreset('healthy')" class="px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-semibold whitespace-nowrap transition">
                Sehat
            </button>
            <button type="button" @click="applyPreset('prediabetes')" class="px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-200 font-semibold whitespace-nowrap transition">
                Prediabetes
            </button>
            <button type="button" @click="applyPreset('diabetes')" class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 font-semibold whitespace-nowrap transition">
                Risiko Tinggi
            </button>
        </div>
    </div>

    <!-- 3-Step Structured Progress Bar & Tabs -->
    <nav aria-label="Tahap Formulir Skrining" class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-2xs space-y-3.5">
        <div class="flex items-center justify-between text-xs font-semibold text-slate-600">
            <span x-text="'Tahap ' + step + ' dari ' + totalSteps"></span>
            <span class="font-mono text-teal-700 font-bold" x-text="Math.round((step / totalSteps) * 100) + '%'"></span>
        </div>
        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full bg-teal-600 rounded-full transition-all duration-300" :style="'width: ' + ((step / totalSteps) * 100) + '%'"></div>
        </div>
        <ol class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs pt-1">
            @foreach([
                1 => ['num' => '01', 'title' => 'Profil & Biometrik', 'sub' => 'Persetujuan, Usia & BMI'],
                2 => ['num' => '02', 'title' => 'Riwayat Medis', 'sub' => 'Hipertensi, Kolesterol, Jantung'],
                3 => ['num' => '03', 'title' => 'Gaya Hidup & Persepsi', 'sub' => 'Aktivitas, Nutrisi, Sosio-Ekonomi'],
            ] as $sNum => $meta)
                <li>
                    <button
                        type="button"
                        @click="if (consent_data || {{ $sNum }} === 1) step = {{ $sNum }}"
                        :aria-current="step === {{ $sNum }} ? 'step' : null"
                        class="w-full text-left px-3.5 py-2.5 rounded-xl border transition flex items-center gap-3"
                        :class="step === {{ $sNum }} ? 'bg-teal-50/80 border-teal-600 text-teal-950 shadow-2xs' : (step > {{ $sNum }} ? 'bg-slate-50 border-teal-200 text-slate-800' : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50')"
                    >
                        <span
                            class="w-7 h-7 rounded-lg flex items-center justify-center font-mono text-xs font-bold shrink-0"
                            :class="step === {{ $sNum }} ? 'bg-teal-700 text-white' : (step > {{ $sNum }} ? 'bg-teal-100 text-teal-800' : 'bg-slate-100 text-slate-500')"
                            x-text="step > {{ $sNum }} ? '✓' : '{{ $meta['num'] }}'"
                        ></span>
                        <span class="min-w-0">
                            <span class="block font-bold truncate">{{ $meta['title'] }}</span>
                            <span class="block text-[11px] opacity-75 truncate">{{ $meta['sub'] }}</span>
                        </span>
                    </button>
                </li>
            @endforeach
        </ol>
    </nav>

    <!-- Main Form (Preserves 100% of Backend Field Names & IDs) -->
    <form action="{{ route('skrining.store') }}" method="POST" class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-6 sm:p-8 space-y-6">
        @csrf

        <!-- STEP 1: Profil, Persetujuan & Biometrik (Consent, Nakes Meta, Sex, Age, BMI) -->
        <fieldset x-show="step === 1" class="space-y-6">
            <legend class="text-lg font-extrabold text-slate-900">1. Profil Pasien, Persetujuan &amp; Data Biometrik</legend>

            @if($user->isNakes() || $user->isAdmin())
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-sky-50/60 border border-sky-200/80">
                    <div>
                        <label for="patient_label" class="block text-xs font-bold text-slate-800 mb-1">Kode Pseudonim Pasien (Opsional / Nakes)</label>
                        <span id="patient-label-hint" class="block text-[11px] text-slate-500 mb-1.5">Gunakan kode anonim seperti PASIEN-015 (UU PDP).</span>
                        <input
                            type="text"
                            id="patient_label"
                            name="patient_label"
                            x-model="patient_label"
                            maxlength="80"
                            aria-describedby="patient-label-hint"
                            placeholder="Misal: PASIEN-KLINIK-015"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm"
                        >
                    </div>
                    <div>
                        <label for="audience" class="block text-xs font-bold text-slate-800 mb-1">Gaya Bahasa Narasi LLM</label>
                        <span id="audience-hint" class="block text-[11px] text-slate-500 mb-1.5">Sesuaikan nada penjelasan hasil skrining.</span>
                        <select id="audience" name="audience" x-model="audience" aria-describedby="audience-hint" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm">
                            <option value="tenaga_medis">Tenaga Medis / Klinisi</option>
                            <option value="awam">Masyarakat Umum / Awam</option>
                        </select>
                    </div>
                </div>
            @endif

            <!-- Sex -->
            <div class="space-y-2">
                <span class="block text-sm font-bold text-slate-800">1. Jenis Kelamin Biologis (<code class="font-mono text-xs text-teal-700">Sex</code>)</span>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition text-sm" :class="form.Sex == 0 ? 'border-teal-600 bg-teal-50/70 font-bold text-teal-950' : 'border-slate-300 hover:bg-slate-50 text-slate-700'">
                        <input type="radio" name="Sex" value="0" x-model.number="form.Sex" class="w-4 h-4 accent-teal-700">
                        <span>Perempuan (0)</span>
                    </label>
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition text-sm" :class="form.Sex == 1 ? 'border-teal-600 bg-teal-50/70 font-bold text-teal-950' : 'border-slate-300 hover:bg-slate-50 text-slate-700'">
                        <input type="radio" name="Sex" value="1" x-model.number="form.Sex" class="w-4 h-4 accent-teal-700">
                        <span>Laki-laki (1)</span>
                    </label>
                </div>
            </div>

            <!-- Age Helper -> Age -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50/80 border border-slate-200/90">
                <div>
                    <label for="age_years" class="block text-sm font-bold text-slate-800 mb-1">Usia Saat Ini (Tahun)</label>
                    <span id="age-years-hint" class="block text-xs text-slate-500 mb-1.5">Minimal 18 tahun (otomatis dipetakan ke skala 1–13).</span>
                    <input
                        type="number"
                        inputmode="numeric"
                        id="age_years"
                        name="age_years"
                        x-model.number="age_years"
                        @input="updateAgeCategory()"
                        min="18"
                        max="115"
                        aria-describedby="age-years-hint"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 font-mono font-semibold"
                    >
                </div>
                <div>
                    <label for="Age" class="block text-sm font-bold text-slate-800 mb-1">2. Kelompok Usia BRFSS (<code class="font-mono text-xs text-teal-700">Age</code>)</label>
                    <span class="block text-xs text-teal-700 font-semibold mb-1.5">Kategori otomatis dari usia tahun:</span>
                    <select id="Age" name="Age" x-model.number="form.Age" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 font-semibold text-sm">
                        @foreach($features['Age']['options'] ?? [] as $k => $lbl)
                            <option value="{{ $k }}">Kategori {{ $k }}: {{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- BMI Calculator -> BMI -->
            <div class="p-4 sm:p-5 rounded-xl bg-teal-50/50 border border-teal-200/90 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">3. Kalkulator Indeks Massa Tubuh (<code class="font-mono text-xs text-teal-700">BMI</code>)</h3>
                        <p class="text-xs text-slate-600">Masukkan berat &amp; tinggi badan untuk menghitung BMI otomatis, atau ubah nilai BMI langsung.</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-bold bg-teal-700 text-white whitespace-nowrap" x-text="'BMI ' + form.BMI + ' · ' + bmiCategory()"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="weight_kg" class="block text-xs font-bold text-slate-700 mb-1">Berat Badan (kg)</label>
                        <input type="number" inputmode="decimal" step="0.1" id="weight_kg" name="weight_kg" x-model.number="weight_kg" @input="updateBmi()" min="25" max="250" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 font-mono">
                    </div>
                    <div>
                        <label for="height_cm" class="block text-xs font-bold text-slate-700 mb-1">Tinggi Badan (cm)</label>
                        <input type="number" inputmode="decimal" step="0.5" id="height_cm" name="height_cm" x-model.number="height_cm" @input="updateBmi()" min="100" max="230" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 font-mono">
                    </div>
                    <div>
                        <label for="BMI" class="block text-xs font-bold text-teal-900 mb-1">Nilai Fitur <code class="font-mono">BMI</code> (12–98)</label>
                        <input type="number" inputmode="decimal" step="0.1" id="BMI" name="BMI" x-model.number="form.BMI" min="12" max="98" required class="w-full rounded-xl border border-teal-600 bg-white px-3.5 py-2 font-mono font-bold text-teal-900">
                    </div>
                </div>
            </div>

            <!-- Clinical Disclaimer & Consent -->
            <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200/90 space-y-3">
                <p class="text-xs text-amber-950 leading-relaxed">{{ $disclaimer }}</p>
                <label for="consent_data" class="flex items-start gap-3 cursor-pointer pt-2 border-t border-amber-200/70">
                    <input type="checkbox" id="consent_data" name="consent_data" value="1" x-model="consent_data" required class="mt-0.5 w-4 h-4 rounded accent-teal-700 shrink-0">
                    <span class="text-xs sm:text-sm text-slate-800 leading-relaxed">
                        <strong>Saya menyetujui</strong> pemrosesan 21 indikator kesehatan ini oleh model DiaXplain dan memahami bahwa hasilnya <strong>bukan diagnosis medis definitif</strong>.
                    </span>
                </label>
            </div>
        </fieldset>

        <!-- STEP 2: Riwayat Medis & Kondisi Kesehatan -->
        <fieldset x-show="step === 2" x-cloak class="space-y-6">
            <legend class="text-lg font-extrabold text-slate-900">2. Riwayat Medis &amp; Indikator Kondisi Klinis</legend>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach([
                    'HighBP' => ['4. Tekanan Darah Tinggi (Hipertensi)', 'Pernah dinyatakan memiliki tekanan darah tinggi oleh dokter/nakes?'],
                    'HighChol' => ['5. Kolesterol Tinggi', 'Pernah dinyatakan memiliki kadar kolesterol darah tinggi?'],
                    'CholCheck' => ['6. Cek Kolesterol (5 Tahun Terakhir)', 'Pernah memeriksa kolesterol darah dalam 5 tahun terakhir?'],
                    'Stroke' => ['7. Riwayat Stroke', 'Pernah didiagnosis mengalami stroke?'],
                    'HeartDiseaseorAttack' => ['8. Penyakit Jantung Koroner / Serangan Jantung', 'Pernah mengalami PJK atau serangan jantung (infark miokard)?'],
                    'DiffWalk' => ['9. Kesulitan Berjalan / Naik Tangga', 'Mengalami kesulitan serius saat berjalan atau menaiki tangga?'],
                ] as $fCode => [$fTitle, $fDesc])
                    <div class="p-4 rounded-xl border border-slate-200/90 bg-slate-50/50 space-y-2.5 flex flex-col justify-between">
                        <div>
                            <span class="block text-sm font-bold text-slate-900">{{ $fTitle }}</span>
                            <span class="block text-xs text-slate-600 mt-0.5">{{ $fDesc }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <label class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border cursor-pointer text-xs font-bold transition" :class="form.{{ $fCode }} == 0 ? 'bg-white border-teal-600 text-teal-800 shadow-2xs' : 'border-slate-300 text-slate-600 hover:bg-white'">
                                <input type="radio" name="{{ $fCode }}" value="0" x-model.number="form.{{ $fCode }}" class="accent-teal-700">
                                <span>Tidak (0)</span>
                            </label>
                            <label class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border cursor-pointer text-xs font-bold transition" :class="form.{{ $fCode }} == 1 ? 'bg-teal-700 border-teal-700 text-white shadow-2xs' : 'border-slate-300 text-slate-600 hover:bg-white'">
                                <input type="radio" name="{{ $fCode }}" value="1" x-model.number="form.{{ $fCode }}" class="accent-white">
                                <span>Ya (1)</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- GenHlth (1..5) -->
            <div class="p-4 rounded-xl border border-slate-200/90 space-y-2.5">
                <span class="block text-sm font-bold text-slate-900">10. Penilaian Kesehatan Umum Mandiri (<code class="font-mono text-xs text-teal-700">GenHlth</code> 1–5)</span>
                <span class="block text-xs text-slate-600">Secara umum, bagaimana Anda menilai kondisi kesehatan Anda saat ini?</span>
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 pt-1">
                    @foreach($features['GenHlth']['options'] ?? [] as $val => $lbl)
                        <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer text-xs font-bold transition" :class="form.GenHlth == {{ $val }} ? 'border-teal-600 bg-teal-50 text-teal-900' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                            <input type="radio" name="GenHlth" value="{{ $val }}" x-model.number="form.GenHlth" class="accent-teal-700">
                            <span>{{ $lbl }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- MentHlth & PhysHlth (0..30 days) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl border border-slate-200/90 space-y-1.5">
                    <label for="MentHlth" class="block text-sm font-bold text-slate-900">11. Hari Kesehatan Mental Kurang Baik (<code class="font-mono text-xs text-teal-700">MentHlth</code>)</label>
                    <span id="ment-hint" class="block text-xs text-slate-500">Jumlah hari stres/tertekan dalam 30 hari terakhir (0–30).</span>
                    <input type="number" inputmode="numeric" id="MentHlth" name="MentHlth" x-model.number="form.MentHlth" min="0" max="30" required aria-describedby="ment-hint" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 font-mono font-bold">
                </div>
                <div class="p-4 rounded-xl border border-slate-200/90 space-y-1.5">
                    <label for="PhysHlth" class="block text-sm font-bold text-slate-900">12. Hari Kesehatan Fisik Kurang Baik (<code class="font-mono text-xs text-teal-700">PhysHlth</code>)</label>
                    <span id="phys-hint" class="block text-xs text-slate-500">Jumlah hari sakit/cedera fisik dalam 30 hari terakhir (0–30).</span>
                    <input type="number" inputmode="numeric" id="PhysHlth" name="PhysHlth" x-model.number="form.PhysHlth" min="0" max="30" required aria-describedby="phys-hint" class="w-full rounded-xl border border-slate-300 px-3.5 py-2 font-mono font-bold">
                </div>
            </div>
        </fieldset>

        <!-- STEP 3: Gaya Hidup, Akses Kesehatan & Sosial-Ekonomi -->
        <fieldset x-show="step === 3" x-cloak class="space-y-6">
            <legend class="text-lg font-extrabold text-slate-900">3. Gaya Hidup, Akses Layanan &amp; Sosial-Ekonomi</legend>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach([
                    'Smoker' => ['13. Riwayat Merokok ≥ 100 Batang', 'Pernah merokok minimal 100 batang (±5–8 bungkus) seumur hidup?'],
                    'PhysActivity' => ['14. Aktivitas Fisik / Olahraga', 'Melakukan olahraga/aktivitas fisik di luar pekerjaan dalam 30 hari terakhir?'],
                    'Fruits' => ['15. Konsumsi Buah Harian', 'Mengonsumsi buah minimal 1 kali setiap hari?'],
                    'Veggies' => ['16. Konsumsi Sayur Harian', 'Mengonsumsi sayuran minimal 1 kali setiap hari?'],
                    'HvyAlcoholConsump' => ['17. Konsumsi Alkohol Berat', 'Konsumsi alkohol berat (pria ≥14 gelas/minggu, wanita ≥7 gelas/minggu)?'],
                    'AnyHealthcare' => ['18. Jaminan Kesehatan (BPJS / Asuransi)', 'Memiliki jaminan atau asuransi kesehatan aktif?'],
                    'NoDocbcCost' => ['19. Kendala Biaya ke Dokter (12 Bulan)', 'Pernah batal berobat ke dokter karena kendala biaya dalam 1 tahun terakhir?'],
                ] as $fCode => [$fTitle, $fDesc])
                    <div class="p-4 rounded-xl border border-slate-200/90 bg-slate-50/50 space-y-2.5 flex flex-col justify-between">
                        <div>
                            <span class="block text-sm font-bold text-slate-900">{{ $fTitle }}</span>
                            <span class="block text-xs text-slate-600 mt-0.5">{{ $fDesc }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <label class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border cursor-pointer text-xs font-bold transition" :class="form.{{ $fCode }} == 0 ? 'bg-white border-teal-600 text-teal-800 shadow-2xs' : 'border-slate-300 text-slate-600 hover:bg-white'">
                                <input type="radio" name="{{ $fCode }}" value="0" x-model.number="form.{{ $fCode }}" class="accent-teal-700">
                                <span>Tidak (0)</span>
                            </label>
                            <label class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border cursor-pointer text-xs font-bold transition" :class="form.{{ $fCode }} == 1 ? 'bg-teal-700 border-teal-700 text-white shadow-2xs' : 'border-slate-300 text-slate-600 hover:bg-white'">
                                <input type="radio" name="{{ $fCode }}" value="1" x-model.number="form.{{ $fCode }}" class="accent-white">
                                <span>Ya (1)</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Socio-Economic (Education & Income) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label for="Education" class="block text-sm font-bold text-slate-900 mb-1">20. Pendidikan Terakhir (<code class="font-mono text-xs text-teal-700">Education</code>)</label>
                    <select id="Education" name="Education" x-model.number="form.Education" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium">
                        @foreach($features['Education']['options'] ?? [] as $k => $lbl)
                            <option value="{{ $k }}">Skala {{ $k }} — {{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="Income" class="block text-sm font-bold text-slate-900 mb-1">21. Pendapatan Rumah Tangga (<code class="font-mono text-xs text-teal-700">Income</code>)</label>
                    <select id="Income" name="Income" x-model.number="form.Income" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium">
                        @foreach($features['Income']['options'] ?? [] as $k => $lbl)
                            <option value="{{ $k }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Live Pre-Submit Summary Strip -->
            <div class="rounded-xl bg-teal-50/70 border border-teal-200/90 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-teal-950">
                <div>
                    <p class="font-bold text-teal-900">Siap Dianalisis oleh Model XGBoost + TreeSHAP + LLM</p>
                    <p class="mt-0.5 text-teal-800">
                        BMI <strong class="font-mono" x-text="form.BMI"></strong> · Kat. Usia <strong class="font-mono" x-text="form.Age"></strong> · Hipertensi <strong x-text="form.HighBP ? 'Ya' : 'Tidak'"></strong> · Kolesterol <strong x-text="form.HighChol ? 'Ya' : 'Tidak'"></strong> · GenHlth <strong class="font-mono" x-text="form.GenHlth"></strong>
                    </p>
                </div>
                <span class="font-mono text-[11px] font-bold px-2.5 py-1 rounded-lg bg-white border border-teal-200 text-teal-800 whitespace-nowrap">21 / 21 Fitur Terisi</span>
            </div>
        </fieldset>

        <!-- Stepper Footer Actions -->
        <div class="pt-5 border-t border-slate-200/80 flex items-center justify-between gap-3">
            <button
                type="button"
                x-show="step > 1"
                x-cloak
                @click="step--; window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm transition tap-target"
            >
                ← Sebelumnya
            </button>
            <div x-show="step === 1"></div>

            <button
                type="button"
                x-show="step < totalSteps"
                @click="if (consent_data) { step++; window.scrollTo({ top: 0, behavior: 'smooth' }); }"
                class="px-6 py-2.5 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-semibold text-sm shadow-xs transition tap-target"
            >
                Lanjut Tahap Berikutnya →
            </button>

            <button
                type="submit"
                x-show="step === totalSteps"
                x-cloak
                enterkeyhint="done"
                class="px-6 py-3 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-sm shadow-sm transition tap-target"
            >
                Proses Analisis XGBoost + SHAP + LLM
            </button>
        </div>
    </form>
</div>
@endsection
