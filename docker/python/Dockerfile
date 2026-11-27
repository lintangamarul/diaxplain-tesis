FROM python:3.11-slim

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    libgomp1 \
    curl \
    && rm -rf /var/lib/apt/lists/*

COPY pyproject.toml requirements.txt ./
COPY src ./src

RUN pip install --no-cache-dir -e .

EXPOSE 8001

CMD ["uvicorn", "diaxplain_ml.serve.app:app", "--host", "0.0.0.0", "--port", "8001"]

