import os
import sys
import json
import time
import math
import logging
import asyncio
from concurrent.futures import ThreadPoolExecutor

import boto3
import numpy as np
import requests
from botocore.client import Config
from botocore.exceptions import ClientError, EndpointResolutionError
from fastapi import FastAPI, Header, HTTPException, Request
from pydantic import BaseModel, Field
from scipy.optimize import minimize
from typing import List, Dict, Optional

# ─── Logging ────────────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s — %(message)s",
)
logger = logging.getLogger("CBT_IRT_Microservice")

# ─── Environment & Startup Validation ────────────────────────────────────────
_INSECURE_DEFAULT = "cbt-secret-key-portal-sma"
SECRET_TOKEN = os.getenv("CBT_INTERNAL_SECRET", "")

if not SECRET_TOKEN:
    logger.critical(
        "FATAL: CBT_INTERNAL_SECRET env var is not set. "
        "Refusing to start — set a strong secret (min 32 chars)."
    )
    sys.exit(1)

if SECRET_TOKEN == _INSECURE_DEFAULT:
    logger.critical(
        "FATAL: CBT_INTERNAL_SECRET is still the insecure default value. "
        "Set a unique, strong secret before running in production."
    )
    sys.exit(1)

# ─── MinIO / S3 Client ───────────────────────────────────────────────────────
MINIO_ENDPOINT  = os.getenv("MINIO_ENDPOINT", "http://127.0.0.1:9000").strip().strip('"').strip("'")
MINIO_ACCESS    = os.getenv("MINIO_ACCESS_KEY", "minioadmin").strip().strip('"').strip("'")
MINIO_SECRET    = os.getenv("MINIO_SECRET_KEY", "minioadmin").strip().strip('"').strip("'")
MINIO_BUCKET    = os.getenv("MINIO_BUCKET", "cbt-analytics").strip().strip('"').strip("'")
MINIO_REGION    = os.getenv("MINIO_REGION", "us-east-1").strip().strip('"').strip("'")

_s3: Optional[boto3.client] = None


def _get_s3() -> boto3.client:
    """Lazy singleton — boto3 client untuk MinIO."""
    global _s3
    if _s3 is None:
        _s3 = boto3.client(
            "s3",
            endpoint_url=MINIO_ENDPOINT,
            aws_access_key_id=MINIO_ACCESS,
            aws_secret_access_key=MINIO_SECRET,
            region_name=MINIO_REGION,
            config=Config(signature_version="s3v4"),
        )
    return _s3


def _ensure_bucket() -> None:
    """Buat bucket jika belum ada."""
    s3 = _get_s3()
    try:
        s3.head_bucket(Bucket=MINIO_BUCKET)
    except ClientError as e:
        code = int(e.response["Error"]["Code"])
        if code == 404:
            s3.create_bucket(Bucket=MINIO_BUCKET)
            logger.info(f"Bucket '{MINIO_BUCKET}' berhasil dibuat.")
        else:
            raise


def _save_to_minio(key: str, data: dict) -> str:
    """
    Simpan dict sebagai JSON ke MinIO.
    Returns: full object key (path) yang disimpan.
    """
    s3 = _get_s3()
    body = json.dumps(data, ensure_ascii=False, default=str).encode("utf-8")
    s3.put_object(
        Bucket=MINIO_BUCKET,
        Key=key,
        Body=body,
        ContentType="application/json",
    )
    logger.info(f"Hasil disimpan ke MinIO: s3://{MINIO_BUCKET}/{key}")
    return key


def _check_minio_health() -> dict:
    """Cek koneksi MinIO. Returns status dict."""
    try:
        _get_s3().list_buckets()
        return {"status": "connected", "endpoint": MINIO_ENDPOINT, "bucket": MINIO_BUCKET}
    except Exception as exc:
        return {"status": "error", "error": str(exc)}


# ─── ThreadPoolExecutor untuk isolasi komputasi CPU-bound ───────────────────
# scipy.optimize.minimize adalah blocking — JANGAN dijalankan di asyncio event
# loop langsung, karena akan membekukan seluruh FastAPI server.
_executor = ThreadPoolExecutor(max_workers=2, thread_name_prefix="irt_worker")


# ─── FastAPI App ─────────────────────────────────────────────────────────────
app = FastAPI(
    title="CBT IRT Analytics Microservice",
    description=(
        "Dedicated microservice untuk estimasi parameter "
        "Item Response Theory (1PL/Rasch, 2PL, 3PL) via MMLE. "
        "Hasil disimpan ke MinIO dan dikirim balik ke Laravel via callback."
    ),
    version="2.0.0",
)


# ─── Pydantic Schemas ────────────────────────────────────────────────────────
class QuestionItem(BaseModel):
    id: int
    score_max: float = 1.0


class StudentResponse(BaseModel):
    student_exam_id: int
    answers: Dict[str, int]  # question_id (str) → binary score (0 or 1)


class IrtEstimateRequest(BaseModel):
    exam_id: Optional[int] = None
    bank_id: Optional[int] = None   # ← FIXED: field bank_id untuk analisis lintas-exam
    job_id: Optional[int] = None
    model_type: str = "2PL"          # "1PL", "RASCH", "2PL", "3PL"
    callback_url: str
    questions: List[QuestionItem]
    responses: List[StudentResponse]

    model_config = {"str_strip_whitespace": True}


# ─── IRT Core: Probability ───────────────────────────────────────────────────
def irt_prob(theta, a, b, c=0.0):
    """Logistic 2PL/3PL probability of correct response P(θ)."""
    return c + (1.0 - c) / (1.0 + np.exp(-a * (theta - b)))


# ─── IRT Core: Parameter Estimation (MMLE / Joint MLE) ───────────────────────
def estimate_irt_parameters(
    model_type: str,
    questions: List[QuestionItem],
    responses: List[StudentResponse],
) -> tuple[list, list]:
    """
    Estimasi parameter IRT (a, b, c) dan kemampuan siswa (θ) via
    alternating optimization (EM-style joint MLE).

    Mendukung:
      - 1PL / Rasch  : a=1 fixed, c=0 fixed, estimasi b
      - 2PL          : estimasi a dan b, c=0 fixed
      - 3PL          : estimasi a, b, dan c (guessing)

    Mengembalikan (item_results, student_results).
    """
    n_students = len(responses)
    n_items = len(questions)

    if n_students == 0 or n_items == 0:
        return [], []

    q_ids = [str(q.id) for q in questions]

    # ── Susun matriks jawaban biner Y [N × M] ───────────────────────────────
    Y = np.zeros((n_students, n_items))
    s_ids = []
    for i, res in enumerate(responses):
        s_ids.append(res.student_exam_id)
        for j, q_id in enumerate(q_ids):
            Y[i, j] = res.answers.get(q_id, 0)

    # ── Initial values ───────────────────────────────────────────────────────
    p_vec = np.clip(np.mean(Y, axis=0), 0.01, 0.99)
    b_params = -np.log(p_vec / (1.0 - p_vec))   # logit transform → b₀
    a_params = np.ones(n_items)
    c_params = np.zeros(n_items)

    raw_perc = np.clip(np.mean(Y, axis=1), 0.01, 0.99)
    thetas = np.log(raw_perc / (1.0 - raw_perc))   # logit → θ₀

    # ── Alternating Optimization (15 iterasi) ────────────────────────────────
    for _iter in range(15):
        # 1. Estimasi θ per siswa (item params fixed)
        for i in range(n_students):
            y_i = Y[i, :]

            def neg_ll_theta(th, a=a_params, b=b_params, c=c_params):
                p = np.clip(irt_prob(th[0], a, b, c), 1e-7, 1 - 1e-7)
                ll = np.sum(y_i * np.log(p) + (1 - y_i) * np.log(1 - p))
                prior = -0.5 * (th[0] ** 2)   # Normal(0,1) prior
                return -(ll + prior)

            res_th = minimize(
                neg_ll_theta, [thetas[i]],
                bounds=[(-4.0, 4.0)], method="L-BFGS-B",
            )
            if res_th.success:
                thetas[i] = res_th.x[0]

        # 2. Estimasi parameter butir soal (thetas fixed)
        mt = model_type.upper()
        for j in range(n_items):
            y_j = Y[:, j]

            if mt in ("1PL", "RASCH"):
                def neg_ll_b(bv, th=thetas):
                    p = np.clip(irt_prob(th, 1.0, bv[0], 0.0), 1e-7, 1 - 1e-7)
                    return -np.sum(y_j * np.log(p) + (1 - y_j) * np.log(1 - p))

                res_b = minimize(
                    neg_ll_b, [b_params[j]],
                    bounds=[(-4.0, 4.0)], method="L-BFGS-B",
                )
                if res_b.success:
                    b_params[j] = res_b.x[0]
                    a_params[j] = 1.0
                    c_params[j] = 0.0

            elif mt in ("2PL", "3PL"):
                use_c = (mt == "3PL")

                def neg_ll_ab(params, th=thetas, is3pl=use_c):
                    a_v = params[0]
                    b_v = params[1]
                    c_v = params[2] if is3pl else 0.0
                    p = np.clip(irt_prob(th, a_v, b_v, c_v), 1e-7, 1 - 1e-7)
                    return -np.sum(y_j * np.log(p) + (1 - y_j) * np.log(1 - p))

                bnds = [(0.2, 3.5), (-4.0, 4.0)]
                x0 = [a_params[j], b_params[j]]
                if use_c:
                    bnds.append((0.0, 0.35))
                    x0.append(c_params[j])

                res_ab = minimize(neg_ll_ab, x0, bounds=bnds, method="L-BFGS-B")
                if res_ab.success:
                    a_params[j] = res_ab.x[0]
                    b_params[j] = res_ab.x[1]
                    if use_c:
                        c_params[j] = res_ab.x[2]

    # ── Format output butir soal ─────────────────────────────────────────────
    item_results = []
    for j, q in enumerate(questions):
        item_results.append({
            "question_id":      q.id,
            "difficulty_b":     float(round(b_params[j], 4)),
            "discrimination_a": float(round(a_params[j], 4)),
            "guessing_c":       float(round(c_params[j], 4)),
            "infit_mnsq":       1.0,   # placeholder (perlu chi² fit stats terpisah)
            "outfit_mnsq":      1.0,
        })

    # ── Format output kemampuan siswa ────────────────────────────────────────
    student_results = []
    theta_mean = float(np.mean(thetas))
    theta_std  = float(np.std(thetas)) or 1.0

    for i, s_id in enumerate(s_ids):
        th = thetas[i]

        # Standard Error: SE(θ) = 1 / √[ΣI(θ)]
        info = np.sum(
            (a_params ** 2)
            * irt_prob(th, a_params, b_params, c_params)
            * (1 - irt_prob(th, a_params, b_params, c_params))
        )
        se = 1.0 / math.sqrt(max(0.01, float(info)))

        # Skor terstandarisasi ke skala UTBK (mean=500, sd=100), clamp 200–800
        scaled = 500.0 + ((th - theta_mean) / theta_std) * 100.0
        scaled = float(max(200.0, min(800.0, scaled)))

        percentile = float(np.mean(thetas <= th) * 100.0)

        student_results.append({
            "student_exam_id": s_id,
            "theta":           float(round(th, 4)),
            "standard_error":  float(round(se, 4)),
            "scaled_score":    float(round(scaled, 2)),
            "percentile":      float(round(percentile, 2)),
        })

    return item_results, student_results


# ─── Background Worker (berjalan di ThreadPoolExecutor) ─────────────────────
def _irt_worker_sync(payload: IrtEstimateRequest) -> None:
    """
    Fungsi BLOCKING yang dijalankan di thread pool.
    Estimasi IRT → simpan ke MinIO → kirim callback ke Laravel.
    """
    exam_id  = payload.exam_id
    bank_id  = payload.bank_id
    job_id   = payload.job_id
    scope_id = exam_id or bank_id

    logger.info(
        f"[IRT Worker] Mulai estimasi — exam_id={exam_id}, bank_id={bank_id}, "
        f"model={payload.model_type}, N={len(payload.responses)}"
    )
    t0 = time.time()

    try:
        item_params, student_abilities = estimate_irt_parameters(
            payload.model_type,
            payload.questions,
            payload.responses,
        )

        elapsed = round(time.time() - t0, 2)
        logger.info(f"[IRT Worker] Estimasi selesai dalam {elapsed} detik.")

        # ── Simpan hasil ke MinIO ────────────────────────────────────────────
        prefix = f"irt/exam_{exam_id}" if exam_id else f"irt/bank_{bank_id}"
        object_key = f"{prefix}/job_{job_id}.json"

        result_payload = {
            "exam_id":          exam_id,
            "bank_id":          bank_id,
            "job_id":           job_id,
            "status":           "completed",
            "model_type":       payload.model_type,
            "elapsed_seconds":  elapsed,
            "item_parameters":  item_params,
            "student_abilities": student_abilities,
        }

        _ensure_bucket()
        _save_to_minio(object_key, result_payload)

        # ── Callback ke Laravel (hanya kirim result_path) ────────────────────
        callback_data = {
            "exam_id":     exam_id,
            "bank_id":     bank_id,
            "job_id":      job_id,
            "status":      "completed",
            "model_type":  payload.model_type,
            "result_path": object_key,     # Laravel baca JSON dari MinIO
        }

        resp = requests.post(
            payload.callback_url,
            json=callback_data,
            headers={
                "X-CBT-Secret":  SECRET_TOKEN,
                "Content-Type":  "application/json",
            },
            timeout=15,
        )
        logger.info(
            f"[IRT Worker] Callback → {payload.callback_url} | HTTP {resp.status_code}"
        )

    except Exception as exc:
        logger.error(
            f"[IRT Worker] Error estimasi (scope={scope_id}): {exc}",
            exc_info=True,
        )
        try:
            requests.post(
                payload.callback_url,
                json={
                    "exam_id":      exam_id,
                    "bank_id":      bank_id,
                    "job_id":       job_id,
                    "status":       "failed",
                    "error_message": str(exc),
                },
                headers={"X-CBT-Secret": SECRET_TOKEN},
                timeout=10,
            )
        except Exception as cb_err:
            logger.error(f"[IRT Worker] Gagal kirim error callback: {cb_err}")


# ─── API Endpoints ────────────────────────────────────────────────────────────

@app.get("/")
@app.get("/api/v1/health")
async def health_check():
    """Health check endpoint — cek koneksi MinIO."""
    minio_status = _check_minio_health()
    return {
        "status":  "ok",
        "service": "CBT IRT Analytics Microservice",
        "version": "2.0.0",
        "minio":   minio_status,
    }


@app.post("/api/v1/irt/estimate")
async def estimate_irt(
    payload: IrtEstimateRequest,
    x_cbt_secret: Optional[str] = Header(None),
):
    """
    Terima request estimasi IRT dari Laravel.
    Validasi secret → cek threshold peserta → dispatch ke ThreadPoolExecutor.
    Return 202 Accepted SEGERA (non-blocking).
    """
    # ── Autentikasi ──────────────────────────────────────────────────────────
    if not x_cbt_secret or x_cbt_secret != SECRET_TOKEN:
        raise HTTPException(status_code=401, detail="Unauthorized: X-CBT-Secret header tidak valid.")

    # ── Validasi exam_id atau bank_id ────────────────────────────────────────
    if not payload.exam_id and not payload.bank_id:
        raise HTTPException(status_code=422, detail="Salah satu dari exam_id atau bank_id wajib diisi.")

    # ── Validasi threshold peserta ───────────────────────────────────────────
    n = len(payload.responses)
    if n < 100:
        raise HTTPException(
            status_code=400,
            detail=f"IRT membutuhkan minimal 100 peserta. Diterima: {n} peserta.",
        )

    # ── Dispatch ke thread pool (CPU-bound, tidak blokir event loop) ─────────
    loop = asyncio.get_event_loop()
    loop.run_in_executor(_executor, _irt_worker_sync, payload)

    scope = f"exam_id={payload.exam_id}" if payload.exam_id else f"bank_id={payload.bank_id}"
    logger.info(f"[API] Estimasi IRT diterima — {scope}, model={payload.model_type}, N={n}")

    return {
        "status":             "accepted",
        "exam_id":            payload.exam_id,
        "bank_id":            payload.bank_id,
        "model_type":         payload.model_type,
        "participants_count": n,
        "message":            f"Estimasi IRT {payload.model_type} berjalan di background worker.",
    }


# ─── Entry point (development / direct run) ──────────────────────────────────
if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        "main:app",
        host="127.0.0.1",
        port=8085,
        workers=2,
        loop="asyncio",
    )
