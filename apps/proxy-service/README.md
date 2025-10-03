# Proxy Management Service (Golang)

A lightweight, high-performance proxy management service written in Go that handles proxy rotation, health checking, and provides a simple REST API for retrieving working proxies.

## Features

✅ **Proxy Pool Management** - Maintains a pool of proxy servers with automatic rotation  
✅ **Health Checking** - Periodic background health checks to ensure proxy availability  
✅ **Smart Rotation** - Round-robin rotation that only returns healthy proxies  
✅ **Auto-Recovery** - Automatically marks proxies as healthy when they recover  
✅ **Rate Limiting** - Built-in rate limiting (120 requests/minute per IP)  
✅ **Concurrent Safe** - Thread-safe operations for handling concurrent requests  
✅ **REST API** - Simple JSON API for integration with other services  
✅ **Logging** - Comprehensive logging of all proxy operations  

## SRS Requirements Implemented

This service implements the following requirements from the Software Requirements Specification:

- **REQ-GO-001**: Service is written in Golang ✅
- **REQ-GO-002**: Maintains a pool of proxy servers ✅
- **REQ-GO-003**: Implements proxy rotation logic ✅
- **REQ-GO-004**: Validates proxy availability before rotation ✅
- **REQ-GO-005**: Exposes HTTP endpoint for proxy retrieval ✅
- **REQ-GO-006**: Returns proxy in format `host:port` ✅
- **REQ-GO-007**: Handles concurrent proxy requests ✅
- **REQ-GO-008**: Removes non-functional proxies from pool ✅
- **REQ-GO-009**: Logs proxy usage and rotation events ✅
- **REQ-GO-API-001**: Exposes endpoint `/proxy/next` ✅
- **REQ-GO-API-002**: Returns JSON response with proxy details ✅
- **REQ-GO-API-003**: Implements rate limiting ✅
- **REQ-GO-API-004**: Runs on configurable port ✅

## Installation

### Prerequisites

- Go 1.20 or higher
- Make (optional, for using Makefile commands)

### Setup

1. **Clone the repository** (if not already done):
   ```bash
   cd proxy-service
   ```

2. **Install dependencies**:
   ```bash
   go mod init proxy-service
   go mod tidy
   ```

3. **Configure proxies**:
   Edit `proxies.json` to add your proxy servers:
   ```json
   [
       "http://proxy1.example.com:8080",
       "http://proxy2.example.com:8080",
       "https://proxy3.example.com:3128",
       "socks5://proxy4.example.com:1080"
   ]
   ```

4. **Set environment variables** (optional):
   ```bash
   export PROXY_SERVICE_PORT=8080
   ```

## Usage

### Running the Service

**Option 1: Using Go directly**
```bash
go run proxy.go
```

**Option 2: Build and run**
```bash
# Build the binary
go build -o bin/proxy-service proxy.go

# Run the binary
./bin/proxy-service
```

**Option 3: Using Make (if Makefile exists)**
```bash
make build
make run
```

### Service Output

When the service starts, you'll see:
```
=== Proxy Management Service ===
REQ-GO-001: Service written in Golang
[CONFIG] Loaded 4 proxies from proxies.json
[INIT] Added proxy: proxy1.example.com (protocol: http)
[INIT] Added proxy: proxy2.example.com (protocol: http)
[HEALTH] Health checking service started (interval: 60s)
[HEALTH] Starting health check for all proxies...
[HEALTH] Proxy proxy1.example.com:8080 HEALTHY (response: 45ms)
[HEALTH] Health check completed: 4/4 proxies healthy
[SERVER] Starting Proxy Management Service on port 8080
[SERVER] Endpoints:
[SERVER]   - GET /proxy/next  : Get next available proxy
[SERVER]   - GET /proxies     : List all proxies
[SERVER]   - GET /health      : Health check
[SERVER] Rate limit: 120 requests/minute per IP
[SERVER] Proxy Management Service is running
[SERVER] Access at http://localhost:8080
```

## API Endpoints

### 1. Get Service Info
**Endpoint:** `GET /`

**Description:** Get service information and statistics

**Example:**
```bash
curl http://localhost:8080/
```

**Response:**
```json
{
  "service": "Proxy Management Service",
  "version": "1.0.0",
  "status": "running",
  "stats": {
    "total_proxies": 4,
    "healthy_proxies": 4,
    "unhealthy_proxies": 0
  },
  "endpoints": {
    "GET /proxy/next": "Get next available proxy",
    "GET /proxies": "List all proxies with status",
    "GET /health": "Service health check"
  }
}
```

---

### 2. Get Next Proxy
**Endpoint:** `GET /proxy/next`

**Description:** Retrieve the next available healthy proxy using round-robin rotation

**Example:**
```bash
curl http://localhost:8080/proxy/next
```

**Success Response (200 OK):**
```json
{
  "proxy": "proxy1.example.com:8080",
  "url": "http://proxy1.example.com:8080",
  "protocol": "http",
  "is_healthy": true,
  "response_time": 45
}
```

**Error Response (503 Service Unavailable):**
```json
{
  "error": "No proxy available",
  "message": "no healthy proxies available (checked 4 proxies)",
  "code": 503
}
```

**Usage in Laravel (PHP):**
```php
use GuzzleHttp\Client;

$client = new Client();
$response = $client->get('http://localhost:8080/proxy/next');
$data = json_decode($response->getBody(), true);

$proxy = $data['proxy']; // "proxy1.example.com:8080"
$proxyUrl = $data['url']; // "http://proxy1.example.com:8080"

// Use the proxy for scraping
$scrapingClient = new Client([
    'proxy' => $proxyUrl,
    'timeout' => 30,
]);
```

---

### 3. List All Proxies
**Endpoint:** `GET /proxies`

**Description:** Get detailed information about all proxies in the pool

**Example:**
```bash
curl http://localhost:8080/proxies
```

**Response:**
```json
{
  "proxies": [
    {
      "url": "http://proxy1.example.com:8080",
      "host": "proxy1.example.com",
      "port": "8080",
      "protocol": "http",
      "is_healthy": true,
      "last_checked": "2025-10-03T15:30:45.123Z",
      "failure_count": 0,
      "success_count": 25,
      "response_time": 45,
      "last_used": "2025-10-03T15:29:12.456Z"
    },
    {
      "url": "http://proxy2.example.com:8080",
      "host": "proxy2.example.com",
      "port": "8080",
      "protocol": "http",
      "is_healthy": false,
      "last_checked": "2025-10-03T15:30:46.789Z",
      "failure_count": 5,
      "success_count": 10,
      "response_time": 0,
      "last_used": "2025-10-03T15:20:30.123Z"
    }
  ],
  "total": 2
}
```

---

### 4. Health Check
**Endpoint:** `GET /health`

**Description:** Check service health and get proxy pool statistics

**Example:**
```bash
curl http://localhost:8080/health
```

**Response:**
```json
{
  "status": "healthy",
  "stats": {
    "total_proxies": 4,
    "healthy_proxies": 3,
    "unhealthy_proxies": 1
  }
}
```

---

## Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `PROXY_SERVICE_PORT` | Port to run the service on | `8080` |

**Example:**
```bash
export PROXY_SERVICE_PORT=9000
go run proxy.go
```

### Proxy Configuration File

Edit `proxies.json` to configure your proxy pool:

```json
[
    "http://proxy1.example.com:8080",
    "http://proxy2.example.com:8080",
    "https://secure-proxy.example.com:3128",
    "socks5://socks-proxy.example.com:1080"
]
```

**Supported Formats:**
- `http://host:port` - HTTP proxy
- `https://host:port` - HTTPS proxy
- `socks5://host:port` - SOCKS5 proxy
- `host:port` - Defaults to HTTP

## Health Checking

The service performs automatic health checks every **60 seconds**:

1. **TCP Connection Test**: Attempts to establish a TCP connection to each proxy
2. **Response Time Tracking**: Measures connection time in milliseconds
3. **Failure Tracking**: Counts consecutive failures
4. **Auto-Recovery**: Proxies marked unhealthy after 3 consecutive failures
5. **Auto-Healing**: Proxies automatically recover when health check succeeds

### Health Check Log Example

```
[HEALTH] Starting health check for all proxies...
[HEALTH] Proxy proxy1.example.com:8080 HEALTHY (response: 45ms)
[HEALTH] Proxy proxy2.example.com:8080 UNHEALTHY (failures: 3, error: dial tcp: i/o timeout)
[HEALTH] Proxy proxy2.example.com:8080 marked as UNHEALTHY after 3 consecutive failures
[HEALTH] Health check completed: 3/4 proxies healthy
```

## Proxy Rotation

The service uses **round-robin rotation** with health validation:

1. Request comes to `/proxy/next`
2. Service selects next proxy in rotation sequence
3. Checks if proxy is healthy
4. If healthy: returns proxy details
5. If unhealthy: tries next proxy in sequence
6. Repeats until healthy proxy found or all proxies checked

### Rotation Log Example

```
[ROTATION] Selected proxy: proxy1.example.com:8080 (health: true, success: 25, failures: 0)
[ROTATION] Selected proxy: proxy2.example.com:8080 (health: true, success: 30, failures: 0)
[ROTATION] Selected proxy: proxy3.example.com:8080 (health: true, success: 15, failures: 0)
```

## Rate Limiting

Built-in rate limiting prevents abuse:

- **Limit**: 120 requests per minute per IP address
- **Window**: 1 minute sliding window
- **Enforcement**: Per client IP (supports X-Forwarded-For header)

**Rate Limit Exceeded Response (429):**
```json
{
  "error": "Rate limit exceeded",
  "message": "Maximum 120 requests per minute allowed",
  "code": 429
}
```

## Integration with Laravel Backend

### Example: Using Proxy Service in Scraping

```php
<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ScrapingService
{
    private const PROXY_SERVICE_URL = 'http://localhost:8080';
    
    public function scrapeProduct(string $productUrl): array
    {
        // Get next available proxy
        $proxy = $this->getNextProxy();
        
        // Create HTTP client with proxy
        $client = new Client([
            'proxy' => $proxy['url'],
            'timeout' => 30,
            'verify' => false, // Disable SSL verification for proxies
            'headers' => [
                'User-Agent' => $this->getRandomUserAgent(),
            ],
        ]);
        
        try {
            $response = $client->get($productUrl);
            return $this->parseProductData($response->getBody());
        } catch (\Exception $e) {
            Log::error('Scraping failed', [
                'url' => $productUrl,
                'proxy' => $proxy['proxy'],
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    private function getNextProxy(): array
    {
        $client = new Client(['timeout' => 5]);
        
        try {
            $response = $client->get(self::PROXY_SERVICE_URL . '/proxy/next');
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Failed to get proxy', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Proxy service unavailable');
        }
    }
    
    private function getRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36...',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36...',
        ];
        return $userAgents[array_rand($userAgents)];
    }
    
    private function parseProductData(string $html): array
    {
        // Parse HTML and extract product data
        // Implementation depends on target platform (Amazon/Jumia)
        return [];
    }
}
```

## Monitoring and Debugging

### Request Logging

All requests are logged with details:
```
[REQUEST] GET /proxy/next from 127.0.0.1:54321
[ROTATION] Selected proxy: proxy1.example.com:8080 (health: true, success: 25, failures: 0)
[REQUEST] Completed in 1.234ms
```

### Health Check Monitoring

Monitor proxy health in real-time:
```bash
# Watch health status
watch -n 5 'curl -s http://localhost:8080/health | jq'

# Check specific proxy details
curl http://localhost:8080/proxies | jq '.proxies[] | select(.is_healthy == false)'
```

## Troubleshooting

### Problem: All proxies marked as unhealthy

**Solution:**
1. Check if proxy servers are actually reachable
2. Verify `proxies.json` has correct host:port format
3. Check firewall rules
4. Review logs for specific connection errors

### Problem: "No healthy proxies available"

**Solution:**
1. Check `/health` endpoint to see proxy status
2. Wait for next health check cycle (60 seconds)
3. Manually verify proxy connectivity
4. Add more proxies to `proxies.json`

### Problem: Rate limit errors

**Solution:**
1. Current limit is 120 requests/minute per IP
2. Implement request queuing in client application
3. Deploy multiple instances of proxy service if needed

## Performance

- **Concurrent Requests**: Handles multiple concurrent requests safely
- **Memory Footprint**: ~5-10MB for typical proxy pool
- **Health Check**: Non-blocking background goroutine
- **Response Time**: <5ms for proxy selection (excluding network latency)

## Development

### Building for Production
```bash
# Build optimized binary
go build -ldflags="-s -w" -o bin/proxy-service proxy.go

# Run in production
./bin/proxy-service
```

---

**Version**: 1.0.0  
**Last Updated**: October 3, 2025
