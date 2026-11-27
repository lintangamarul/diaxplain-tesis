# Panduan Konfigurasi API Key LLM Pihak Ketiga & Evaluasi Narasi (RM6)

DiaXplain dirancang menggunakan **Arsitektur Adapter Multi-Provider** berbasis **API Key Pihak Ketiga** (tanpa ketergantungan pada Ollama lokal), dilengkapi **7 Lapis Guardrail Validasi Fakta** dan **Fallback Templat Deterministik**.

---

## 1. Cara Memasukkan API Key LLM Pihak Ketiga

Anda dapat memasukkan atau mengganti API Key kapan saja melalui **dua cara**:

### Cara 1 — Langsung dari Halaman Admin UI (Direkomendasikan)
1. Login sebagai **Admin** (`admin@diaxplain.id` / `password`).
2. Buka menu **Konfigurasi AI / LLM** (`/admin/llm-config`).
3. Pilih **Provider Layanan AI**:
   - **OpenAI / OpenAI-Compatible API** (`https://api.openai.com/v1`, atau Groq `https://api.groq.com/openai/v1`, atau OpenRouter `https://openrouter.ai/api/v1`, atau DeepSeek `https://api.deepseek.com/v1`)
   - **Google Gemini API** (`https://generativelanguage.googleapis.com/v1beta/openai` dengan model `gemini-2.5-flash`)
   - **Anthropic Claude API** (`https://api.anthropic.com/v1` dengan model `claude-3-5-haiku-latest`)
4. Masukkan **API Key** Anda pada kolom input yang tersedia, lalu klik **Simpan & Uji Koneksi API**.

### Cara 2 — Melalui File `.env`
Edit file `ml/.env` (dan/atau `web/.env`):
```env
LLM_PROVIDER=openai_compatible
LLM_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxxx
LLM_BASE_URL=https://api.openai.com/v1
LLM_MODEL=gpt-4o-mini
LLM_TEMPERATURE=0.2
PROMPT_VERSION=p1.0
```

---

## 2. Rubrik Penilaian Pakar / Tenaga Medis (Skala Likert 1–5)

Untuk menjawab Rumusan Masalah 6 (RM6), Tenaga Medis dapat masuk ke menu **Penilaian Narasi AI** (`/nakes/penilaian`) dan menilai kualitas narasi pada 4 dimensi:
1. **Akurasi terhadap Hasil Model (1–5):** Kesesuaian label prediksi, probabilitas, dan arah faktor SHAP.
2. **Kejelasan Bahasa (1–5):** Kemudahan dipahami oleh masyarakat awam maupun tenaga kesehatan.
3. **Kegunaan Praktis (1–5):** Manfaat saran gaya hidup umum sebagai pendukung keputusan skrining awal.
4. **Keamanan & Kehati-hatian Medis (1–5):** Tidak adanya klaim diagnosis pasti, resep obat, atau dosis.

