#!/bin/bash
set -euo pipefail

BOOTSTRAP="${KAFKA_BOOTSTRAP:-kafka:9092}"
TOPICS_FILE="${TOPICS_FILE:-/etc/kafka/topics.json}"
KAFKA_TOPICS="/opt/kafka/bin/kafka-topics.sh"

if [ ! -f "$TOPICS_FILE" ]; then
  exit 0
fi

while IFS= read -r topic; do
  [ -z "$topic" ] && continue
  "$KAFKA_TOPICS" --bootstrap-server "$BOOTSTRAP" \
    --create --if-not-exists \
    --topic "$topic" \
    --partitions 3 \
    --replication-factor 1
done < <(grep -o '"name"[[:space:]]*:[[:space:]]*"[^"]*"' "$TOPICS_FILE" | sed 's/.*"\([^"]*\)"$/\1/')
