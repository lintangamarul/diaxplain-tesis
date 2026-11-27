@extends('layouts.app')

@section('title', 'Formulir Skrining Status Diabetes (21 Indikator BRFSS)')

@section('content')
<div
    x-data="{
        step: 1,
        totalSteps: 6,
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
            if (b < 18.5) return 'Berat Badan Kurang (Underweight)';
            if (b < 23.0) return 'Normal Ideal (Asia-Pasifik)';
            if (b < 25.0) return 'Normal Batas Atas (WHO Normal)';
            if (b < 30.0) return 'Berat Badan Berlebih (Overweight)';
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
    <!-- Header & Preset Simulator Bar -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Skrining Status Diabetes (21 Indikator BRFSS)</h1>
            <p class="text-xs sm:text-sm text-slate-600 mt-0.5">Isi 6 langkah terpandu di bawah ini untuk memperoleh prediksi XGBoost, grafik kontribusi SHAP lokal, dan narasi AI.</p>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 text-xs">
            <span class="text-slate-500 font-semibold mr-1">Isi Cepat Contoh Kasus:</span>
            <button type="button" @click="applyPreset('healthy')" class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-semibold transition">
                Profil Sehat
            </button>
            <button type="button" @click="applyPreset('prediabetes')" class="px-2.5 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-200 font-semibold transition">
                Profil Prediabetes
            </button>
            <button type="button" @click="applyPreset('diabetes')" class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 font-semibold transition">
                Profil Risiko Tinggi
            </button>
        </div>
    </div>

    <!-- Accessible Multi-Step Progress Tracker -->
    <nav aria-label="Progres Langkah Skrining" class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-2xs">
        <ol class="grid grid-cols-2 sm:grid-cols-6 gap-2 text-xs">
            @foreach([
                1 => '1. Persetujuan',
                2 => '2. Fisik & Usia',
                3 => '3. Riwayat Medis',
                4 => '4. Gaya Hidup',
                5 => '5. Sosial-Ekonomi',
                6 => '6. Tinjau & Kirim'
            ] as $sNum => $sLabel)
                <li>
                    <button
                        type="button"
                        @click="if (consent_data) step = {{ $sNum }}"
                        :aria-current="step === {{ $sNum }} ? 'step' : null"
                        class="w-full text-left px-3 py-2.5 rounded-xl font-bold transition flex items-center justify-between"
                        :class="step === {{ $sNum }} ? 'bg-teal-600 text-white shadow-xs' : (step > {{ $sNum }} ? 'bg-teal-50 text-teal-800 border border-teal-200' : 'bg-slate-100 text-slate-600')"
                    >
                        <span class="truncate">{{ $sLabel }}</span>
                        <span x-show="step > {{ $sNum }}" aria-hidden="true" class="text-teal-600 font-extrabold">✓</span>
                    </button>
                </li>
            @endforeach
        </ol>
    </nav>

    <!-- Main Form -->
    <form action="{{ route('skrining.store') }}" method="POST" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        @csrf

        <!-- STEP 1: Consent & Patient Label (if Nakes/Admin) -->
        <fieldset x-show="step === 1" class="space-y-5">
            <legend class="text-lg font-extrabold text-slate-900">Langkah 1 dari 6 — Persetujuan & Tujuan Skrining</legend>

            <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 text-xs sm:text-sm text-amber-950 space-y-2 leading-relaxed">
                <p class="font-bold text-amber-900">Pernyataan Penting Sebelum Memulai:</p>
                <p>{{ $disclaimer }}</p>
            </div>

            @if($user->isNakes() || $user->isAdmin())
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-sky-50/70 border border-sky-200">
                    <div>
                        <label for="patient_label" class="block text-sm font-bold text-slate-800 mb-1">Kode Pseudonim Pasien (Khusus Nakes)</label>
                        <span id="patient-label-hint" class="block text-xs text-slate-500 mb-1.5">Jangan gunakan nama asli/NIK pasien (UU PDP). Gunakan kode seperti PASIEN-015.</span>
                        <input
                            type="text"
                            id="patient_label"
                            name="patient_label"
                            x-model="patient_label"
                            maxlength="80"
                            aria-describedby="patient-label-hint"
                            placeholder="Misal: PASIEN-KLINIK-015"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm"
                        >
                    </div>
                    <div>
                        <label for="audience" class="block text-sm font-bold text-slate-800 mb-1">Gaya Bahasa Narasi LLM</label>
                        <span id="audience-hint" class="block text-xs text-slate-500 mb-1.5">Pilih target pembaca penjelasan hasil skrining.</span>
                        <select id="audience" name="audience" x-model="audience" aria-describedby="audience-hint" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm">
                            <option value="tenaga_medis">Tenaga Medis / Klinisi</option>
                            <option value="awam">Masyarakat Umum / Awam</option>
                        </select>
                    </div>
                </div>
            @endif

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <label for="consent_data" class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" id="consent_data" name="consent_data" value="1" x-model="consent_data" required class="mt-1 w-5 h-5 rounded accent-teal-600">
                    <span class="text-sm text-slate-800 leading-relaxed">
                        <strong>Saya menyetujui</strong> bahwa 21 indikator kesehatan yang saya masukkan akan dianalisis secara otomatis oleh model Machine Learning DiaXplain, dan saya memahami bahwa hasil ini <strong>bukan diagnosis medis</strong>.
                    </span>
                </label>
            </div>
        </fieldset>

        <!-- STEP 2: Demografi & Fisik (Sex, Age, BMI) -->
        <fieldset x-show="step === 2" x-cloak class="space-y-6">
            <legend class="text-lg font-extrabold text-slate-900">Langkah 2 dari 6 — Data Fisik, Kalkulator BMI & Usia</legend>

            <!-- Sex (1. Sex) -->
            <div class="space-y-2">
                <span class="block text-sm font-bold text-slate-800">1. Jenis Kelamin Biologis (<code>Sex</code>)</span>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition" :class="form.Sex == 0 ? 'border-teal-600 bg-teal-50/70 font-bold' : 'border-slate-300 hover:bg-slate-50'">
                        <input type="radio" name="Sex" value="0" x-model.number="form.Sex" class="w-4 h-4 accent-teal-600">
                        <span>Perempuan (0)</span>
                    </label>
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition" :class="form.Sex == 1 ? 'border-teal-600 bg-teal-50/70 font-bold' : 'border-slate-300 hover:bg-slate-50'">
                        <input type="radio" name="Sex" value="1" x-model.number="form.Sex" class="w-4 h-4 accent-teal-600">
                        <span>Laki-laki (1)</span>
                    </label>
                </div>
            </div>

            <!-- Age Helper -> Age (2. Age) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div>
                    <label for="age_years" class="block text-sm font-bold text-slate-800 mb-1">Usia Anda Saat Ini (Tahun)</label>
                    <span id="age-years-hint" class="block text-xs text-slate-500 mb-1.5">Minimal 18 tahun (otomatis memilih kategori <code>Age</code> 1–13).</span>
                    <input
                        type="number"
                        id="age_years"
                        name="age_years"
                        x-model.number="age_years"
                        @input="updateAgeCategory()"
                        min="18"
                        max="115"
                        aria-describedby="age-years-hint"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5"
                    >
                </div>
                <div>
                    <label for="Age" class="block text-sm font-bold text-slate-800 mb-1">2. Kelompok Usia BRFSS (<code>Age</code> 1–13)</label>
                    <span class="block text-xs text-teal-700 font-semibold mb-1.5">Dikonversi otomatis dari usia tahun:</span>
                    <select id="Age" name="Age" x-model.number="form.Age" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 font-semibold">
                        @foreach($features['Age']['options'] ?? [] as $k => $lbl)
                            <option value="{{ $k }}">Kategori {{ $k }}: {{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- BMI Calculator -> BMI (3. BMI) -->
            <div class="p-4 rounded-xl bg-teal-50/50 border border-teal-200 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">3. Kalkulator Indeks Massa Tubuh (<code>BMI</code>)</h3>
                        <p class="text-xs text-slate-600">Masukkan berat & tinggi badan untuk menghitung BMI otomatis, atau sesuaikan langsung nilai BMI (12–98).</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-teal-100 text-teal-900" x-text="'BMI: ' + form.BMI + ' kg/m² — ' + bmiCategory()"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="weight_kg" class="block text-xs font-bold text-slate-700 mb-1">Berat Badan (kg)</label>
                        <input type="number" step="0.1" id="weight_kg" name="weight_kg" x-model.number="weight_kg" @input="updateBmi()" min="25" max="250" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2">
                    </div>
                    <div>
                        <label for="height_cm" class="block text-xs font-bold text-slate-700 mb-1">Tinggi Badan (cm)</label>
                        <input type="number" step="0.5" id="height_cm" name="height_cm" x-model.number="height_cm" @input="updateBmi()" min="100" max="230" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2">
                    </div>
                    <div>
                        <label for="BMI" class="block text-xs font-bold text-teal-900 mb-1">Nilai Fitur <code>BMI</code> (12–98)</label>
                        <input type="number" step="0.1" id="BMI" name="BMI" x-model.number="form.BMI" min="12" max="98" required class="w-full rounded-xl border border-teal-500 bg-white px-3.5 py-2 font-bold text-teal-900">
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- STEP 3: Riwayat & Kondisi Kesehatan (9 fitur) -->
        <fieldset x-show="step === 3" x-cloak class="space-y-6">
            <legend class="text-lg font-extrabold text-slate-900">Langkah 3 dari 6 — Riwayat & Kondisi Kesehatan</legend>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach([
                    'HighBP' => ['4. Tekanan Darah Tinggi (Hipertensi)', 'Pernah dinyatakan memiliki tekanan darah tinggi oleh dokter/nakes?'],
                    'HighChol' => ['5. Kolesterol Tinggi', 'Pernah dinyatakan memiliki kadar kolesterol darah tinggi?'],
                    'CholCheck' => ['6. Cek Kolesterol (5 Tahun Terakhir)', 'Pernah memeriksa kolesterol dalam 5 tahun terakhir?'],
                    'Stroke' => ['7. Riwayat Stroke', 'Pernah didiagnosis mengalami stroke?'],
                    'HeartDiseaseorAttack' => ['8. Penyakit Jantung Koroner / Serangan Jantung', 'Pernah mengalami PJK atau serangan jantung (infark miokard)?'],
                    'DiffWalk' => ['9. Kesulitan Berjalan / Naik Tangga', 'Mengalami kesulitan serius saat berjalan atau menaiki tangga?'],
                ] as $fCode => [$fTitle, $fDesc])
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 space-y-2.5">
                        <span class="block text-sm font-bold text-slate-900">{{ $fTitle }}</span>
                        <span class="block text-xs text-slate-600">{{ $fDesc }}</span>
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <label class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border cursor-pointer text-xs font-bold transition" :class="form.{{ $fCode }} == 0 ? 'bg-white border-teal-600 text-teal-800 shadow-2xs' : 'border-slate-300 text-slate-600'">
                                <input type="radio" name="{{ $fCode }}" value="0" x-model.number="form.{{ $fCode }}" class="accent-teal-600">
                                <span>Tidak (0)</span>
                            </label>
                            <label class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border cursor-pointer text-xs font-bold transition" :class="form.{{ $fCode }} == 1 ? 'bg-teal-600 border-teal-600 text-white shadow-2xs' : 'border-slate-300 text-slate-600'">
                                <input type="radio" name="{{ $fCode }}" value="1" x-model.number="form.{{ $fCode }}" class="accent-white">
                                <span>Ya (1)</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- GenHlth (1..5) -->
            <div class="p-4 rounded-xl border border-slate-200 space-y-2.5">
                <span class="block text-sm font-bold text-slate-900">10. Penilaian Kesehatan Umum Mandiri (<code>GenHlth</code> 1–5)</span>
                <span class="block text-xs text-slate-600">Secara umum, bagaimana Anda menilai kondisi kesehatan Anda saat ini?</span>
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 pt-1">
                    @foreach($features['GenHlth']['options'] ?? [] as $val => $lbl)
                        <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer text-xs font-bold transition" :class="form.GenHlth == {{ $val }} ? 'border-teal-600 bg-teal-50 text-teal-900' : 'border-slate-200 hover:bg-slate-50'">
                            <input type="radio" name="GenHlth" value="{{ $val }}" x-model.number="form.GenHlth" class="accent-teal-600">
                            <span>{{ $lbl }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- MentHlth & PhysHlth (0..30 days) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl border border-slate-200 space-y-1.5">
                    <label for="MentHlth" class="block text-sm font-bold text-slate-900">11. Hari Kesehatan Mental Kurang Baik (<code>MentHlth</code>)</label>
                    <span id="ment-hint" class="block text-xs text-slate-500">Jumlah hari stres/tertekan dalam 30 hari terakhir (0–30 hari).</span>
                    <input type="number" id="MentHlth" name="MentHlth" x-model.number="form.MentHlth" min="0" max="30" required aria-describedby="ment-hint" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-bold">
                </div>
                <div class="p-4 rounded-xl border border-slate-200 space-y-1.5">
                    <label for="PhysHlth" class="block text-sm font-bold text-slate-900">12. Hari Kesehatan Fisik Kurang Baik (<code>PhysHlth</code>)</label>
                    <span id="phys-hint" class="block text-xs text-slate-500">Jumlah hari sakit/cedera fisik dalam 30 hari terakhir (0–30 hari).</span>
                    <input type="number" id="PhysHlth" name="PhysHlth" x-model.number="form.PhysHlth" min="0" max="30" required aria-describedby="phys-hint" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-bold">
                </div>
            </div>
        </fieldset>

        <!-- STEP 4: Gaya Hidup & Akses Layanan Kesehatan (7 fitur) -->
        <fieldset x-show="step === 4" x-cloak class="space-y-6">
            <legend class="text-lg font-extrabold text-slate-900">Langkah 4 dari 6 — Gaya Hidup & Akses Layanan Kesehatan</legend>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach([
                    'Smoker' => ['13. Riwayat Merokok ≥ 100 Batang', 'Pernah merokok minimal 100 batang (±5–8 bungkus) seumur hidup?'],
                    'PhysActivity' => ['14. Aktivitas Fisik / Olahraga', 'Melakukan olahraga/aktivitas fisik di luar pekerjaan dalam 30 hari terakhir?'],
                    'Fruits' => ['15. Konsumsi Buah Harian', 'Mengonsumsi buah minimal 1 kali setiap hari?'],
                    'Veggies' => ['16. Konsumsi Sayur Harian', 'Mengonsumsi sayuran minimal 1 kali setiap hari?'],
                    'HvyAlcoholConsump' => ['17. Konsumsi Alkohol Berat', 'Konsumsi alkohol berat (pria ≥14 gelas/minggu, wanita ≥7 gelas/minggu)?'],
                    'AnyHealthcare' => ['18. Jaminan Kesehatan (BPJS / Asuransi)', 'Memiliki jaminan atau asuransi kesehatan aktif?'],
                    'NoDocbcCost' => ['19. Kendala Biaya ke Dokter (12 Bulan)', 'Pernah batal berobat ke dokter karena terkendala biaya dalam 1 tahun terakhir?'],
                ] as $fCode => [$fTitle, $fDesc])
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 space-y-2.5">
                        <span class="block text-sm font-bold text-slate-900">{{ $fTitle }}</span>
                        <span class="block text-xs text-slate-600">{{ $fDesc }}</span>
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <label class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border cursor-pointer text-xs font-bold transition" :class="form.{{ $fCode }} == 0 ? 'bg-white border-teal-600 text-teal-800 shadow-2xs' : 'border-slate-300 text-slate-600'">
                                <input type="radio" name="{{ $fCode }}" value="0" x-model.number="form.{{ $fCode }}" class="accent-teal-600">
                                <span>Tidak (0)</span>
                            </label>
                            <label class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border cursor-pointer text-xs font-bold transition" :class="form.{{ $fCode }} == 1 ? 'bg-teal-600 border-teal-600 text-white shadow-2xs' : 'border-slate-300 text-slate-600'">
                                <input type="radio" name="{{ $fCode }}" value="1" x-model.number="form.{{ $fCode }}" class="accent-white">
                                <span>Ya (1)</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </fieldset>

        <!-- STEP 5: Sosial-Ekonomi (Education & Income) -->
        <fieldset x-show="step === 5" x-cloak class="space-y-6">
            <legend class="text-lg font-extrabold text-slate-900">Langkah 5 dari 6 — Indikator Sosial-Ekonomi</legend>

            <div class="rounded-xl bg-slate-100 p-4 text-xs text-slate-700 leading-relaxed">
                <strong>Catatan Metodologis:</strong> Variabel <code>Education</code> dan <code>Income</code> merupakan bagian dari 21 fitur standar dataset BRFSS 2015. Pada narasi SPK, indikator ini dijelaskan semata-mata sebagai pola statistik survei populasi.
            </div>

            <div class="space-y-4">
                <div>
                    <label for="Education" class="block text-sm font-bold text-slate-900 mb-1">20. Tingkat Pendidikan Terakhir (<code>Education</code> 1–6)</label>
                    <select id="Education" name="Education" x-model.number="form.Education" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 font-medium">
                        @foreach($features['Education']['options'] ?? [] as $k => $lbl)
                            <option value="{{ $k }}">Skala {{ $k }} — {{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="Income" class="block text-sm font-bold text-slate-900 mb-1">21. Tingkat Pendapatan Rumah Tangga (<code>Income</code> Skala Relatif 1–8)</label>
                    <span id="income-hint" class="block text-xs text-slate-500 mb-1.5">Pilih tingkat 1 (kelompok pendapatan terendah) hingga 8 (kelompok pendapatan tertinggi).</span>
                    <select id="Income" name="Income" x-model.number="form.Income" aria-describedby="income-hint" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 font-medium">
                        @foreach($features['Income']['options'] ?? [] as $k => $lbl)
                            <option value="{{ $k }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </fieldset>

        <!-- STEP 6: Review & Submit -->
        <fieldset x-show="step === 6" x-cloak class="space-y-6">
            <legend class="text-lg font-extrabold text-slate-900">Langkah 6 dari 6 — Tinjau Ringkasan 21 Fitur & Proses Prediksi</legend>

            <p class="text-xs sm:text-sm text-slate-600">
                Periksa kembali ringkasan ke-21 indikator kesehatan Anda sebelum dikirim ke layanan model <strong>XGBoost + TreeSHAP + LLM</strong>.
            </p>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                <template x-for="(val, key) in form" :key="key">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                        <span class="font-mono font-semibold text-slate-600" x-text="key"></span>
                        <strong class="text-slate-900 font-bold" x-text="val"></strong>
                    </div>
                </template>
            </div>

            <div class="p-4 rounded-xl bg-teal-50 border border-teal-200 text-xs text-teal-950 flex items-center justify-between">
                <span>Setelah tombol diklik, sistem menghitung probabilitas 3 kelas + SHAP lokal secara sinkron (&lt; 2 detik) dan menyusun interpretasi AI.</span>
            </div>
        </fieldset>

        <!-- Stepper Navigation Buttons -->
        <div class="pt-4 border-t border-slate-200 flex items-center justify-between gap-3">
            <button
                type="button"
                x-show="step > 1"
                @click="step--"
                enterkeyhint="previous"
                class="px-5 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-sm transition tap-target"
            >
                ← Langkah Sebelumnya
            </button>
            <div x-show="step === 1"></div>

            <button
                type="button"
                x-show="step < totalSteps"
                @click="if (consent_data) step++"
                enterkeyhint="next"
                class="px-6 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-sm shadow-xs transition tap-target"
            >
                Lanjut ke Langkah Berikutnya →
            </button>

            <button
                type="submit"
                x-show="step === totalSteps"
                x-cloak
                enterkeyhint="done"
                class="px-7 py-3 rounded-xl bg-gradient-to-r from-teal-600 to-sky-600 hover:from-teal-700 hover:to-sky-700 text-white font-extrabold text-sm shadow-md transition tap-target"
            >
                Proses Klasifikasi XGBoost + SHAP + LLM Sekarang
            </button>
        </div>
    </form>
</div>
@endsection

