<?php

namespace Tests\Feature;

use App\Models\Interpretation;
use App\Models\Screening;
use App\Models\Setting;
use App\Models\User;
use App\Services\FeatureMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiaXplainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_feature_mapper_computes_bmi_and_age_category_correctly(): void
    {
        $this->assertSame(1, FeatureMapper::ageYearsToCategory(21));
        $this->assertSame(8, FeatureMapper::ageYearsToCategory(57));
        $this->assertSame(13, FeatureMapper::ageYearsToCategory(84));

        $bmi = FeatureMapper::computeBmi(78, 165);
        $this->assertEqualsWithDelta(28.7, $bmi, 0.1);

        $summary = FeatureMapper::buildInputSummary([
            'HighBP' => 1,
            'HighChol' => 1,
            'CholCheck' => 1,
            'BMI' => 29.4,
            'Smoker' => 0,
            'Stroke' => 0,
            'HeartDiseaseorAttack' => 0,
            'PhysActivity' => 0,
            'Fruits' => 1,
            'Veggies' => 1,
            'HvyAlcoholConsump' => 0,
            'AnyHealthcare' => 1,
            'NoDocbcCost' => 0,
            'GenHlth' => 3,
            'MentHlth' => 2,
            'PhysHlth' => 4,
            'DiffWalk' => 0,
            'Sex' => 1,
            'Age' => 7,
            'Education' => 5,
            'Income' => 6,
        ]);

        $this->assertCount(21, $summary);
    }

    public function test_public_pages_and_dashboard_render_cleanly(): void
    {
        $this->get('/')->assertStatus(200)->assertSee('DiaXplain');
        $this->get('/tentang-model')->assertStatus(200)->assertSee('SHAP');

        $user = User::where('email', 'pengguna@diaxplain.id')->firstOrFail();
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('Skrining');
    }

    public function test_screening_submission_creates_prediction_shap_and_interpretation(): void
    {
        Http::fake([
            '*/v1/assess' => Http::response([
                'model_version' => 'v1.0-E6',
                'predicted_class' => 1,
                'label' => 'Prediabetes',
                'risk_level' => 'Sedang (Perlu Kewaspadaan & Perubahan Gaya Hidup)',
                'probabilities' => [
                    'Non-Diabetes' => 0.25,
                    'Prediabetes' => 0.55,
                    'Diabetes' => 0.20,
                ],
                'top_probability' => 0.55,
                'shap_local' => [
                    'target_class' => 1,
                    'target_label' => 'Prediabetes',
                    'base_value' => -0.35,
                    'prediction_margin' => 0.42,
                    'sum_shap' => 0.77,
                    'additive_diff' => 0.0,
                    'additive_verified' => true,
                    'top_k' => [
                        [
                            'feature' => 'BMI',
                            'label_id' => 'Indeks Massa Tubuh (BMI)',
                            'value' => 29.4,
                            'display_value' => '29.4 kg/m²',
                            'shap_value' => 0.38,
                            'abs_shap' => 0.38,
                            'direction' => 'mendorong_risiko',
                        ],
                    ],
                ],
                'interpretation' => [
                    'ringkasan' => 'Hasil skrining menunjukkan profil Prediabetes (55,0%) dengan BMI 29,4 kg/m².',
                    'faktor_pendorong' => [
                        [
                            'fitur' => 'Indeks Massa Tubuh (BMI)',
                            'nilai_pengguna' => '29.4 kg/m²',
                            'arah' => 'mendorong_risiko',
                            'nilai_shap' => 0.38,
                            'penjelasan' => 'BMI di atas 25 meningkatkan resistensi insulin.',
                        ],
                    ],
                    'faktor_penahan' => [],
                    'langkah_tindak_lanjut' => [
                        'Lakukan olahraga aerobik 150 menit per minggu.',
                    ],
                    'pertanyaan_dokter' => [
                        'Apakah saya perlu pemeriksaan HbA1c?',
                    ],
                    'disclaimer' => 'Bukan pengganti diagnosis medis dokter.',
                    'provider' => 'openai_compatible',
                    'model_name' => 'gpt-4o-mini',
                    'prompt_version' => 'p1.0',
                    'used_fallback' => false,
                    'latency_ms' => 420,
                    'validation_report' => ['passed' => true],
                ],
            ], 200),
        ]);

        $user = User::where('email', 'pengguna@diaxplain.id')->firstOrFail();

        $response = $this->actingAs($user)->post('/skrining', [
            'consent_data' => '1',
            'height_cm' => 170,
            'weight_kg' => 85,
            'age_years' => 52,
            'HighBP' => 1,
            'HighChol' => 1,
            'CholCheck' => 1,
            'Smoker' => 0,
            'Stroke' => 0,
            'HeartDiseaseorAttack' => 0,
            'PhysActivity' => 0,
            'Fruits' => 1,
            'Veggies' => 1,
            'HvyAlcoholConsump' => 0,
            'AnyHealthcare' => 1,
            'NoDocbcCost' => 0,
            'GenHlth' => 3,
            'MentHlth' => 2,
            'PhysHlth' => 4,
            'DiffWalk' => 0,
            'Sex' => 1,
            'Education' => 5,
            'Income' => 6,
        ]);

        $screening = Screening::where('user_id', $user->id)->latest('id')->firstOrFail();
        $response->assertRedirect(route('skrining.show', $screening));

        $this->actingAs($user)
            ->get(route('skrining.show', $screening))
            ->assertStatus(200)
            ->assertSee('Prediabetes')
            ->assertSee('Indeks Massa Tubuh (BMI)');

        $this->actingAs($user)
            ->get(route('skrining.narasi', $screening))
            ->assertStatus(200)
            ->assertJsonPath('status', 'done');

        $this->actingAs($user)
            ->get(route('skrining.pdf', $screening))
            ->assertStatus(200);
    }

    public function test_rbac_and_idor_policy_protection(): void
    {
        $budi = User::where('email', 'pengguna@diaxplain.id')->firstOrFail();
        $otherUser = User::create([
            'name' => 'Siti Pengguna',
            'email' => 'siti@diaxplain.id',
            'password' => bcrypt('password123'),
            'role' => 'pengguna',
            'is_active' => true,
        ]);

        $budiScreening = Screening::where('user_id', $budi->id)->firstOrFail();

        // IDOR check: Siti cannot view Budi's screening
        $this->actingAs($otherUser)
            ->get(route('skrining.show', $budiScreening))
            ->assertStatus(403);

        // RBAC check: pengguna cannot access /admin/* or /nakes/*
        $this->actingAs($budi)->get('/admin/eksperimen')->assertStatus(403);
        $this->actingAs($budi)->get('/admin/llm-config')->assertStatus(403);
        $this->actingAs($budi)->get('/nakes/penilaian')->assertStatus(403);

        // Nakes can view and rate narratives
        $nakes = User::where('email', 'nakes@diaxplain.id')->firstOrFail();
        $this->actingAs($nakes)->get('/nakes/penilaian')->assertStatus(200);

        $interp = Interpretation::firstOrFail();
        $this->actingAs($nakes)
            ->post(route('nakes.ratings.store', $interp), [
                'akurasi' => 5,
                'kejelasan' => 5,
                'kegunaan' => 4,
                'keamanan' => 5,
                'komentar' => 'Sangat informatif dan sesuai nilai SHAP.',
            ])
            ->assertRedirect();

        // Admin can access experiment dashboard and configure 3rd-party LLM API key
        $admin = User::where('email', 'admin@diaxplain.id')->firstOrFail();
        $this->actingAs($admin)->get('/admin/eksperimen')->assertStatus(200)->assertSee('E0');
        $this->actingAs($admin)->get('/admin/llm-config')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/models')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/users')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/audit-logs')->assertStatus(200);

        Http::fake([
            '*/v1/llm/config' => Http::response([
                'status' => 'updated',
                'test_result' => ['ok' => true, 'message' => 'Koneksi berhasil'],
            ], 200),
        ]);

        $this->actingAs($admin)
            ->post('/admin/llm-config', [
                'provider' => 'openai_compatible',
                'api_key' => 'sk-test-thirdparty-key-1234567890',
                'base_url' => 'https://api.openai.com/v1',
                'model' => 'gpt-4o-mini',
                'temperature' => 0.2,
                'prompt_version' => 'p1.0',
                'shap_top_k' => 6,
                'test_connection' => 1,
            ])
            ->assertRedirect();

        $this->assertSame('sk-test-thirdparty-key-1234567890', Setting::getValue('llm_api_key'));
    }

    public function test_saas_ui_refactor_multi_role_rendering_and_clean_display_names(): void
    {
        // Guest layout pages
        $this->get('/')->assertStatus(200)->assertSee('Tiga Tahap Analisis Terstruktur');
        $this->get('/login')->assertStatus(200)->assertSee('Masuk ke DiaXplain');
        $this->get('/register')->assertStatus(200)->assertSee('Daftar Akun DiaXplain');
        $this->get('/tentang-model')->assertStatus(200)->assertSee('Codebook 21 Fitur');

        // Clean display names (no duplicated role suffix)
        $pengguna = User::where('email', 'pengguna@diaxplain.id')->firstOrFail();
        $nakes = User::where('email', 'nakes@diaxplain.id')->firstOrFail();
        $admin = User::where('email', 'admin@diaxplain.id')->firstOrFail();

        $this->assertSame('Budi Santoso', $pengguna->displayName());
        $this->assertSame('dr. Ratna Kusuma, Sp.PD', $nakes->displayName());
        $this->assertSame('Lintang Amarul Fatah', $admin->displayName());

        // Demo login flash message has no duplicated role suffix
        $this->post('/demo-login/pengguna')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Berhasil masuk sebagai akun demo: Budi Santoso — Pengguna Umum.');

        // Pengguna Umum workspace & pages
        $this->actingAs($pengguna)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('Selamat datang, Budi Santoso')
            ->assertDontSee('Budi Santoso (Pengguna Umum)')
            ->assertDontSee('Klinis &amp; Evaluasi', false)
            ->assertDontSee('Riset &amp; Sistem', false);

        $this->actingAs($pengguna)->get('/skrining/baru')->assertStatus(200)->assertSee('Profil &amp; Biometrik', false);
        $this->actingAs($pengguna)->get('/skrining/riwayat')->assertStatus(200)->assertSee('Privasi &amp; Hak Penghapusan Data Pribadi', false);
        $this->actingAs($pengguna)->get('/skrining/bandingkan')->assertStatus(200)->assertSee('Legenda Kontribusi Fitur TreeSHAP');

        // Tenaga Medis workspace
        $this->actingAs($nakes)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('dr. Ratna Kusuma, Sp.PD')
            ->assertDontSee('dr. Ratna Kusuma, Sp.PD (Tenaga Medis)')
            ->assertSee('Penilaian Narasi (RM6)');

        $this->actingAs($nakes)->get('/nakes/penilaian')->assertStatus(200)->assertSee('Formulir Rubrik Likert');

        // Admin tabbed workspace & governance pages
        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('Ringkasan Sistem &amp; AI', false)
            ->assertSee('OpenAI Compatible')
            ->assertDontSee('openai_compatible (gpt-4o-mini)');

        $this->actingAs($admin)->get('/admin/eksperimen')->assertStatus(200)->assertSee('Matriks Perbandingan Eksperimen');
        $this->actingAs($admin)->get('/admin/llm-config')->assertStatus(200)->assertSee('Konfigurasi API Key Layanan AI');
        $this->actingAs($admin)->get('/admin/models')->assertStatus(200)->assertSee('Registri Versi Model');
        $this->actingAs($admin)->get('/admin/users')->assertStatus(200)->assertSee('Lintang Amarul Fatah');
        $this->actingAs($admin)->get('/admin/audit-logs')->assertStatus(200)->assertSee('Jejak Audit Aktivitas');
    }
}
