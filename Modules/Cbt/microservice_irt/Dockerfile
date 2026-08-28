FROM python:3.11-slim

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements and install Python packages
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy microservice code
COPY . .

EXPOSE 8085

# Health check (cek tiap 30 detik, mulai setelah 10 detik, timeout 5 detik, 3 percobaan)
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8085/api/v1/health || exit 1

# Gunakan 2 workers dan asyncio event loop (CPU-bound dihandle via ThreadPoolExecutor di dalam app)
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8085", "--workers", "2", "--loop", "asyncio"]
