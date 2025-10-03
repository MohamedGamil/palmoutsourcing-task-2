package main

import (
	"encoding/json"
	"fmt"
	"log"
	"net"
	"net/http"
	"os"
	"strings"
	"sync"
	"sync/atomic"
	"time"
)

// ProxyInfo represents detailed information about a proxy server
// REQ-GO-002: Maintains a pool of proxy servers with metadata
type ProxyInfo struct {
	URL          string    `json:"url"`           // Full proxy URL (e.g., http://host:port)
	Host         string    `json:"host"`          // Proxy host
	Port         string    `json:"port"`          // Proxy port
	Protocol     string    `json:"protocol"`      // http, https, socks5
	IsHealthy    bool      `json:"is_healthy"`    // Current health status
	LastChecked  time.Time `json:"last_checked"`  // Last health check timestamp
	FailureCount int       `json:"failure_count"` // Consecutive failure count
	SuccessCount int       `json:"success_count"` // Total successful uses
	ResponseTime int64     `json:"response_time"` // Last response time in milliseconds
	LastUsed     time.Time `json:"last_used"`     // Last time this proxy was used
}

// ProxyPool manages a pool of proxy servers with rotation and health checking
// REQ-GO-003: Implements proxy rotation logic
// REQ-GO-004: Validates proxy availability before rotation
// REQ-GO-008: Removes non-functional proxies from pool
type ProxyPool struct {
	proxies      []*ProxyInfo
	current      uint64
	mu           sync.RWMutex
	healthTicker *time.Ticker
	stopChan     chan struct{}
}

// ProxyResponse is the JSON response structure for API clients
// REQ-GO-API-002: Returns JSON response with proxy details
type ProxyResponse struct {
	Proxy        string `json:"proxy"`         // Proxy in host:port format
	URL          string `json:"url"`           // Full proxy URL
	Protocol     string `json:"protocol"`      // Protocol (http, https, socks5)
	IsHealthy    bool   `json:"is_healthy"`    // Health status
	ResponseTime int64  `json:"response_time"` // Average response time in ms
}

// ErrorResponse is the JSON error response structure
type ErrorResponse struct {
	Error   string `json:"error"`
	Message string `json:"message"`
	Code    int    `json:"code"`
}

// NewProxyPool creates and initializes a new proxy pool
// REQ-GO-002: Maintains a pool of proxy servers
func NewProxyPool(proxyURLs []string) *ProxyPool {
	proxies := make([]*ProxyInfo, 0, len(proxyURLs))

	for _, proxyURL := range proxyURLs {
		info := parseProxyURL(proxyURL)
		if info != nil {
			proxies = append(proxies, info)
			log.Printf("[INIT] Added proxy: %s (protocol: %s)", info.Host, info.Protocol)
		}
	}

	pool := &ProxyPool{
		proxies:  proxies,
		current:  0,
		stopChan: make(chan struct{}),
	}

	// Start background health checking
	pool.startHealthChecking()

	return pool
}

// parseProxyURL parses a proxy URL and extracts host, port, and protocol
func parseProxyURL(proxyURL string) *ProxyInfo {
	proxyURL = strings.TrimSpace(proxyURL)
	if proxyURL == "" {
		return nil
	}

	info := &ProxyInfo{
		URL:         proxyURL,
		IsHealthy:   true, // Assume healthy initially
		LastChecked: time.Now(),
	}

	// Extract protocol
	if strings.HasPrefix(proxyURL, "http://") {
		info.Protocol = "http"
		proxyURL = strings.TrimPrefix(proxyURL, "http://")
	} else if strings.HasPrefix(proxyURL, "https://") {
		info.Protocol = "https"
		proxyURL = strings.TrimPrefix(proxyURL, "https://")
	} else if strings.HasPrefix(proxyURL, "socks4://") {
		info.Protocol = "socks4"
		proxyURL = strings.TrimPrefix(proxyURL, "socks4://")
	} else if strings.HasPrefix(proxyURL, "socks5://") {
		info.Protocol = "socks5"
		proxyURL = strings.TrimPrefix(proxyURL, "socks5://")
	} else {
		// Default to http if no protocol specified
		info.Protocol = "http"
	}

	// Extract host and port
	parts := strings.Split(proxyURL, ":")
	if len(parts) == 2 {
		info.Host = parts[0]
		info.Port = parts[1]
	} else {
		info.Host = proxyURL
		info.Port = "80" // Default port
	}

	return info
}

// GetNextProxy returns the next available healthy proxy using round-robin
// REQ-GO-003: Implements proxy rotation logic
// REQ-GO-004: Validates proxy availability before rotation
// REQ-GO-007: Handles concurrent proxy requests
func (p *ProxyPool) GetNextProxy() (*ProxyInfo, error) {
	p.mu.RLock()
	defer p.mu.RUnlock()

	if len(p.proxies) == 0 {
		return nil, fmt.Errorf("no proxies available in the pool")
	}

	// Try to find a healthy proxy (max attempts = pool size)
	attempts := 0
	maxAttempts := len(p.proxies)

	for attempts < maxAttempts {
		index := atomic.AddUint64(&p.current, 1) % uint64(len(p.proxies))
		proxy := p.proxies[index]

		// REQ-GO-004: Validate proxy availability before rotation
		if proxy.IsHealthy {
			proxy.LastUsed = time.Now()
			proxy.SuccessCount++

			// REQ-GO-009: Log proxy usage and rotation events
			log.Printf("[ROTATION] Selected proxy: %s:%s (health: %v, success: %d, failures: %d)",
				proxy.Host, proxy.Port, proxy.IsHealthy, proxy.SuccessCount, proxy.FailureCount)

			return proxy, nil
		}

		attempts++
	}

	// All proxies are unhealthy
	return nil, fmt.Errorf("no healthy proxies available (checked %d proxies)", maxAttempts)
}

// getRetryLimit retrieves the retry limit from environment variable or defaults to 3
func getRetryLimit() int {
	retryLimit := 3 // Default

	if val := os.Getenv("PROXY_SERVICE_MAX_RETRIES"); val != "" {
		if n, err := fmt.Sscanf(val, "%d", &retryLimit); err == nil && n == 1 && retryLimit > 0 {
			return retryLimit
		}
	}

	return retryLimit
}

// checkProxyHealth validates if a proxy is reachable and functional
// REQ-GO-004: Validates proxy availability
func (p *ProxyPool) checkProxyHealth(proxy *ProxyInfo) bool {
	// Create a connection with timeout
	timeout := 5 * time.Second
	start := time.Now()

	conn, err := net.DialTimeout("tcp", fmt.Sprintf("%s:%s", proxy.Host, proxy.Port), timeout)

	elapsed := time.Since(start).Milliseconds()
	proxy.ResponseTime = elapsed
	proxy.LastChecked = time.Now()

	if err != nil {
		proxy.FailureCount++
		// REQ-GO-009: Log proxy usage and rotation events
		log.Printf("[HEALTH] Proxy %s:%s UNHEALTHY (failures: %d, error: %v)",
			proxy.Host, proxy.Port, proxy.FailureCount, err)

		// REQ-GO-008: Remove non-functional proxies (mark as unhealthy after 3 failures)
		if proxy.FailureCount >= getRetryLimit() {
			proxy.IsHealthy = false
			log.Printf("[HEALTH] Proxy %s:%s marked as UNHEALTHY after %d consecutive failures",
				proxy.Host, proxy.Port, proxy.FailureCount)
		}
		return false
	}

	conn.Close()

	// Reset failure count on success
	proxy.FailureCount = 0
	proxy.IsHealthy = true

	log.Printf("[HEALTH] Proxy %s:%s HEALTHY (response: %dms)", proxy.Host, proxy.Port, elapsed)
	return true
}

func getHealthCheckInterval() time.Duration {
	interval := 60 // Default interval in seconds

	if val := os.Getenv("PROXY_SERVICE_HEALTHCHECK_INTERVAL"); val != "" {
		if n, err := fmt.Sscanf(val, "%d", &interval); err == nil && n == 1 && interval > 0 {
			return time.Duration(interval)
		}
	}

	return time.Duration(interval)
}

// startHealthChecking starts background health checking for all proxies
// REQ-GO-004: Validates proxy availability before rotation
// REQ-GO-008: Removes non-functional proxies from pool
func (p *ProxyPool) startHealthChecking() {
	// Health check interval: 60 seconds
	healthCheckInterval := getHealthCheckInterval()
	p.healthTicker = time.NewTicker(healthCheckInterval * time.Second)

	go func() {
		log.Println("[HEALTH] Health checking service started (interval: ", healthCheckInterval, "seconds)")

		// Run initial health check
		p.runHealthCheck()

		for {
			select {
			case <-p.healthTicker.C:
				p.runHealthCheck()
			case <-p.stopChan:
				p.healthTicker.Stop()
				log.Println("[HEALTH] Health checking service stopped")
				return
			}
		}
	}()
}

// runHealthCheck performs health check on all proxies
func (p *ProxyPool) runHealthCheck() {
	p.mu.Lock()
	defer p.mu.Unlock()

	log.Println("[HEALTH] Starting health check for all proxies...")

	var wg sync.WaitGroup
	for _, proxy := range p.proxies {
		wg.Add(1)
		go func(prx *ProxyInfo) {
			defer wg.Done()
			p.checkProxyHealth(prx)
		}(proxy)
	}

	wg.Wait()

	// Count healthy proxies
	healthyCount := 0
	for _, proxy := range p.proxies {
		if proxy.IsHealthy {
			healthyCount++
		}
	}

	log.Printf("[HEALTH] Health check completed: %d/%d proxies healthy", healthyCount, len(p.proxies))
}

// Stop gracefully stops the proxy pool
func (p *ProxyPool) Stop() {
	close(p.stopChan)
}

// GetProxyStats returns statistics about the proxy pool
func (p *ProxyPool) GetProxyStats() map[string]interface{} {
	p.mu.RLock()
	defer p.mu.RUnlock()

	healthyCount := 0
	for _, proxy := range p.proxies {
		if proxy.IsHealthy {
			healthyCount++
		}
	}

	return map[string]interface{}{
		"total_proxies":     len(p.proxies),
		"healthy_proxies":   healthyCount,
		"unhealthy_proxies": len(p.proxies) - healthyCount,
	}
}

// Handler for GET /proxy/next endpoint
// REQ-GO-API-001: Exposes endpoint /proxy/next
// REQ-GO-API-002: Returns JSON response with proxy details
func (p *ProxyPool) handleNextProxy(w http.ResponseWriter, r *http.Request) {
	// Only allow GET requests
	if r.Method != http.MethodGet {
		sendErrorResponse(w, "Method not allowed", "Only GET requests are supported", http.StatusMethodNotAllowed)
		return
	}

	// REQ-GO-007: Handles concurrent proxy requests
	proxy, err := p.GetNextProxy()
	if err != nil {
		sendErrorResponse(w, "No proxy available", err.Error(), http.StatusServiceUnavailable)
		return
	}

	// REQ-GO-006: Returns proxy in format host:port (in response)
	response := ProxyResponse{
		Proxy:        fmt.Sprintf("%s:%s", proxy.Host, proxy.Port),
		URL:          proxy.URL,
		Protocol:     proxy.Protocol,
		IsHealthy:    proxy.IsHealthy,
		ResponseTime: proxy.ResponseTime,
	}

	sendJSONResponse(w, response, http.StatusOK)
}

// Handler for GET /health endpoint
func (p *ProxyPool) handleHealth(w http.ResponseWriter, r *http.Request) {
	stats := p.GetProxyStats()
	sendJSONResponse(w, map[string]interface{}{
		"status": "healthy",
		"stats":  stats,
	}, http.StatusOK)
}

// Handler for GET /proxies endpoint - list all proxies with status
func (p *ProxyPool) handleListProxies(w http.ResponseWriter, r *http.Request) {
	p.mu.RLock()
	defer p.mu.RUnlock()

	sendJSONResponse(w, map[string]interface{}{
		"proxies": p.proxies,
		"total":   len(p.proxies),
	}, http.StatusOK)
}

// sendJSONResponse sends a JSON response
func sendJSONResponse(w http.ResponseWriter, data interface{}, statusCode int) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(statusCode)
	json.NewEncoder(w).Encode(data)
}

// sendErrorResponse sends a JSON error response
func sendErrorResponse(w http.ResponseWriter, error string, message string, statusCode int) {
	response := ErrorResponse{
		Error:   error,
		Message: message,
		Code:    statusCode,
	}
	sendJSONResponse(w, response, statusCode)
}

// loadProxiesFromFile loads proxy URLs from JSON file
func loadProxiesFromFile(filename string) []string {
	file, err := os.Open(filename)
	if err != nil {
		log.Printf("[CONFIG] Could not open proxies file %s: %v", filename, err)
		return []string{}
	}
	defer file.Close()

	var proxies []string
	decoder := json.NewDecoder(file)
	if err := decoder.Decode(&proxies); err != nil {
		log.Printf("[CONFIG] Could not parse proxies file %s: %v", filename, err)
		return []string{}
	}

	log.Printf("[CONFIG] Loaded %d proxies from %s", len(proxies), filename)
	return proxies
}

// getPortFromEnv gets the server port from environment variable or returns default
func getPortFromEnv() string {
	port := os.Getenv("PROXY_SERVICE_PORT")
	if port == "" {
		port = "8080" // Default port
	}
	return port
}

func getRateLimit() int {
	rateLimit := 120 // Default rate limit

	if val := os.Getenv("PROXY_SERVICE_RATE_LIMIT"); val != "" {
		if n, err := fmt.Sscanf(val, "%d", &rateLimit); err == nil && n == 1 && rateLimit > 0 {
			return rateLimit
		}
	}

	return rateLimit
}

// rateLimitMiddleware implements basic rate limiting
// REQ-GO-API-003: Implements rate limiting
func rateLimitMiddleware(next http.HandlerFunc) http.HandlerFunc {
	var (
		requests = make(map[string][]time.Time)
		mu       sync.Mutex
	)

	limit := getRateLimit()
	window := time.Minute

	return func(w http.ResponseWriter, r *http.Request) {
		mu.Lock()
		defer mu.Unlock()

		// Get client IP
		ip := r.RemoteAddr
		if forwarded := r.Header.Get("X-Forwarded-For"); forwarded != "" {
			ip = forwarded
		}

		// Clean old requests outside the time window
		now := time.Now()
		if times, exists := requests[ip]; exists {
			var validRequests []time.Time
			for _, t := range times {
				if now.Sub(t) < window {
					validRequests = append(validRequests, t)
				}
			}
			requests[ip] = validRequests
		}

		// Check if limit exceeded
		if len(requests[ip]) >= limit {
			sendErrorResponse(w, "Rate limit exceeded",
				fmt.Sprintf("Maximum %d requests per minute allowed", limit),
				http.StatusTooManyRequests)
			return
		}

		// Add current request
		requests[ip] = append(requests[ip], now)

		next(w, r)
	}
}

// loggingMiddleware logs all HTTP requests
func loggingMiddleware(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		start := time.Now()
		log.Printf("[REQUEST] %s %s from %s", r.Method, r.URL.Path, r.RemoteAddr)
		next(w, r)
		log.Printf("[REQUEST] Completed in %v", time.Since(start))
	}
}

func getServiceTimeout() time.Duration {
	timeout := 10 // Default timeout in seconds

	if val := os.Getenv("PROXY_SERVICE_REQUEST_TIMEOUT"); val != "" {
		if n, err := fmt.Sscanf(val, "%d", &timeout); err == nil && n == 1 && timeout > 0 {
			return time.Duration(timeout)
		}
	}

	return time.Duration(timeout)
}

func main() {
	log.SetFlags(log.Ldate | log.Ltime | log.Lmicroseconds | log.Lshortfile)
	log.Println("=== Proxy Management Service ===")
	log.Println("REQ-GO-001: Service written in Golang")

	// Load proxies from file or use defaults
	proxies := loadProxiesFromFile("proxies.json")

	// Fallback to default proxies if file is empty
	if len(proxies) == 0 {
		log.Println("[CONFIG] No proxies loaded from file, using default proxies")
		proxies = []string{
			"http://108.141.130.146:80",
		}
	}

	// REQ-GO-002: Initialize proxy pool
	pool := NewProxyPool(proxies)
	defer pool.Stop()

	// Setup HTTP routes
	http.HandleFunc("/", loggingMiddleware(func(w http.ResponseWriter, r *http.Request) {
		stats := pool.GetProxyStats()
		sendJSONResponse(w, map[string]interface{}{
			"service": "Proxy Management Service",
			"version": "1.0.0",
			"status":  "running",
			"stats":   stats,
			"endpoints": map[string]string{
				"GET /proxy/next": "Get next available proxy",
				"GET /proxies":    "List all proxies with status",
				"GET /health":     "Service health check",
			},
		}, http.StatusOK)
	}))

	// REQ-GO-API-001: Expose endpoint /proxy/next
	http.HandleFunc("/proxy/next", loggingMiddleware(rateLimitMiddleware(pool.handleNextProxy)))

	// Additional endpoints
	http.HandleFunc("/proxies", loggingMiddleware(pool.handleListProxies))
	http.HandleFunc("/health", loggingMiddleware(pool.handleHealth))

	// REQ-GO-API-004: Service runs on configurable port
	port := getPortFromEnv()
	serviceTimeout := getServiceTimeout()
	rateLimit := getRateLimit()
	addr := fmt.Sprintf(":%s", port)

	log.Printf("[SERVER] Starting Proxy Management Service on port %s", port)
	log.Printf("[SERVER] Endpoints:")
	log.Printf("[SERVER]   - GET /proxy/next  : Get next available proxy")
	log.Printf("[SERVER]   - GET /proxies     : List all proxies")
	log.Printf("[SERVER]   - GET /health      : Health check")
	log.Printf("[SERVER] Rate limit: %d requests/minute per IP", rateLimit)

	server := &http.Server{
		Addr:         addr,
		ReadTimeout:  serviceTimeout * time.Second,
		WriteTimeout: serviceTimeout * time.Second,
		IdleTimeout:  60 * time.Second,
	}

	// Graceful shutdown
	go func() {
		if err := server.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.Fatalf("[SERVER] Failed to start server: %v", err)
		}
	}()

	log.Println("[SERVER] Proxy Management Service is running")
	log.Printf("[SERVER] Access at http://localhost:%s", port)

	// Wait for interrupt signal
	select {}
}
