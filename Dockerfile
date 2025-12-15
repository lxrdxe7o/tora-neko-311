FROM python:3.10-slim

WORKDIR /app

# Install system dependencies required for potential compilation (though we try to use binary wheels)
# and curl for healthchecks
RUN apt-get update && apt-get install -y \
    curl \
    pkg-config \
    default-libmysqlclient-dev \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

# Expose port 5000 for Flask
EXPOSE 5000

# Command to run the application
CMD ["python", "server.py"]
