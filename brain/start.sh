#!/bin/bash
cd /var/www/html/automatization_fb_google/brain
source venv/bin/activate
exec python -m uvicorn app.main:app --host 0.0.0.0 --port 8002