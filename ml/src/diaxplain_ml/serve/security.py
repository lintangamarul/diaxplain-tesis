"""Service-to-service token authentication (`X-Service-Token`)."""

from __future__ import annotations

import os
from fastapi import Header, HTTPException, status


def verify_service_token(x_service_token: str | None = Header(default=None)) -> str:
    expected = os.getenv("SERVICE_TOKEN", "diaxplain-internal-secret-token-2026")
    if not x_service_token or x_service_token != expected:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="X-Service-Token tidak valid atau tidak disertakan.",
        )
    return x_service_token

