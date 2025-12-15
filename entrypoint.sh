#!/bin/bash
echo "Waiting for database..."
# Simple wait loop or rely on depends_on + healthcheck
# The docker-compose depends_on condition: service_healthy should be enough for connection
# But we might need to wait for the socket or port to be truly ready for connections from the python lib

echo "Initializing database..."
python init_db.py

echo "Starting server..."
python server.py
