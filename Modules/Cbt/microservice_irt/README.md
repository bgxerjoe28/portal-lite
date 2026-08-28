# CBT IRT Analytics Microservice (Python FastAPI)

Microservice khusus untuk estimasi parameter **Item Response Theory (IRT)**:
- **1PL / Rasch Model**
- **2PL (2-Parameter Logistic)**
- **3PL (3-Parameter Logistic dengan parameter tebakan `c`)**

Hasil estimasi disimpan ke **MinIO** (`bucket: cbt-analytics`), lalu Laravel
membacanya via callback + SDK S3 — bukan dump data besar lewat HTTP body.

---

## Persyaratan

- Python 3.11+
- MinIO berjalan di `127.0.0.1:9000` (atau sesuaikan `MINIO_ENDPOINT`)
- Minimal **100 peserta** per sesi ujian ($N \ge 100$) untuk konvergensi parameter

---

## Cara Menjalankan (Development / Laragon)

### 1. Install dependensi

```bash
cd Modules/Cbt/microservice_irt
pip install -r requirements.txt
```

### 2. Set environment variables

```bash
# Windows PowerShell
$env:CBT_INTERNAL_SECRET = "secret-kuat-min-32-karakter-xxxxx"
$env:MINIO_ENDPOINT      = "http://127.0.0.1:9000"
$env:MINIO_ACCESS_KEY    = "minioadmin"
$env:MINIO_SECRET_KEY    = "minioadmin"
$env:MINIO_BUCKET        = "cbt-analytics"
```

### 3. Jalankan microservice

```bash
uvicorn main:app --host 127.0.0.1 --port 8085 --workers 2 --loop asyncio
```

### 4. Verifikasi health check

```bash
curl http://127.0.0.1:8085/api/v1/health
```

Response yang diharapkan:
```json
{
  "status": "ok",
  "service": "CBT IRT Analytics Microservice",
  "version": "2.0.0",
  "minio": { "status": "connected", "endpoint": "http://127.0.0.1:9000", "bucket": "cbt-analytics" }
}
```

---

## Deploy ke Production (Systemd)

Production menggunakan **FrankenPHP + Laravel Octane** tanpa Docker.
Microservice dikelola sebagai **systemd service**.

```bash
# Copy unit file ke server
sudo cp cbt-irt.service /etc/systemd/system/cbt-irt.service

# Buat file environment (ISI DENGAN NILAI NYATA!)
sudo mkdir -p /etc/portal
sudo nano /etc/portal/cbt-irt.env

# Aktifkan dan start
sudo systemctl daemon-reload
sudo systemctl enable cbt-irt
sudo systemctl start cbt-irt

# Cek status
sudo systemctl status cbt-irt
journalctl -u cbt-irt -f
```

---

## Konfigurasi Laravel `.env`

```env
# IRT Microservice
IRT_MICROSERVICE_URL=http://127.0.0.1:8085/api/v1/irt/estimate
CBT_INTERNAL_SECRET=SAMA_DENGAN_YANG_DI_cbt-irt.env
CBT_IRT_MIN_PARTICIPANTS=100

# MinIO (s3_local disk — juga dipakai untuk analytics bucket)
LOCAL_AWS_ACCESS_KEY_ID=minioadmin
LOCAL_AWS_SECRET_ACCESS_KEY=minioadmin
LOCAL_AWS_DEFAULT_REGION=us-east-1
LOCAL_AWS_BUCKET=portal-sma
LOCAL_AWS_URL=http://127.0.0.1:9000
LOCAL_AWS_ENDPOINT=http://127.0.0.1:9000
LOCAL_AWS_USE_PATH_STYLE_ENDPOINT=true

# Bucket khusus hasil analisis IRT/CTT
CBT_ANALYTICS_BUCKET=cbt-analytics
```

---

## Alur Data

```
Laravel POST /api/v1/irt/estimate
        ↓ (202 Accepted — langsung)
FastAPI dispatch ke ThreadPoolExecutor
        ↓ (non-blocking asyncio)
Estimasi IRT (scipy optimize, 15 iterasi)
        ↓
Simpan JSON → MinIO: cbt-analytics/irt/exam_{id}/job_{id}.json
        ↓
POST callback ke Laravel { result_path: "..." }
        ↓
Laravel baca JSON dari MinIO → simpan ke PostgreSQL → hapus file MinIO
```
