#!/bin/bash
# Brain daemon launcher - keeps service running
LOG="/tmp/brain-daemon.log"

while true; do
    cd /var/www/html/automatization_fb_google/brain
    source venv/bin/activate
    echo "Starting brain at $(date)" >> $LOG
    python -m uvicorn app.main:app --host 0.0.0.0 --port 8002 >> $LOG 2>&1
    echo "Brain crashed at $(date), restarting..." >> $LOG
    sleep 2
done