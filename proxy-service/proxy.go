package main

import (
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"net/http/httputil"
	"net/url"
	"os"
	"sync/atomic"
)

type ProxyPool struct {
	proxies []string
	current uint64
}

func (p *ProxyPool) nextProxy() string {
	index := atomic.AddUint64(&p.current, 1) % uint64(len(p.proxies))
	return p.proxies[index]
}

func (p *ProxyPool) proxyHandler(w http.ResponseWriter, r *http.Request) {
	// Get target URL from query parameter or header
	targetURL := r.URL.Query().Get("url")
	if targetURL == "" {
		targetURL = r.Header.Get("X-Target-URL")
	}
	if targetURL == "" {
		http.Error(w, "Target URL not specified. Use ?url=<target> parameter", http.StatusBadRequest)
		return
	}

	// URL decode the target URL if it's encoded
	if decodedURL, err := url.QueryUnescape(targetURL); err == nil {
		targetURL = decodedURL
	}

	// Parse target URL
	target, err := url.Parse(targetURL)
	if err != nil {
		http.Error(w, "Invalid target URL", http.StatusBadRequest)
		return
	}

	proxyURL := p.nextProxy()
	// Parse proxy URL
	proxy, err := url.Parse(proxyURL)
	if err != nil {
		http.Error(w, "Invalid proxy URL", http.StatusInternalServerError)
		return
	}

	// Log the current proxy and target destination
	log.Printf("Using proxy: %s for target: %s", proxyURL, targetURL)

	// Create reverse proxy pointing to target
	reverseProxy := httputil.NewSingleHostReverseProxy(target)

	// Configure proxy transport to use the proxy server
	reverseProxy.Transport = &http.Transport{
		Proxy: http.ProxyURL(proxy),
	}

	// Modify the request to remove the /proxy prefix and use the target path
	originalDirector := reverseProxy.Director
	reverseProxy.Director = func(req *http.Request) {
		originalDirector(req)
		req.URL.Scheme = target.Scheme
		req.URL.Host = target.Host
		req.URL.Path = target.Path
		req.URL.RawQuery = target.RawQuery
		req.Host = target.Host
	}

	// Serve the request through proxy
	reverseProxy.ServeHTTP(w, r)
}

func loadProxiesFromFile(filename string) []string {
	file, err := os.Open(filename)
	if err != nil {
		log.Printf("Could not open proxies file %s: %v", filename, err)
		return []string{}
	}
	defer file.Close()

	var proxies []string
	decoder := json.NewDecoder(file)
	if err := decoder.Decode(&proxies); err != nil {
		log.Printf("Could not parse proxies file %s: %v", filename, err)
		return []string{}
	}

	return proxies
}

func main() {
	// Try to load proxies from JSON file, fallback to default list
	proxies := loadProxiesFromFile("proxies.json")

	// Predefined list of proxies, can be extended or loaded from config, database, etc.
	if len(proxies) == 0 {
		proxies = []string{
			"http://108.141.130.146:80",
		}
	}

	pool := &ProxyPool{
		proxies: proxies,
		current: 0,
	}

	http.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) {
		fmt.Fprintf(w, "Proxy service is running. Use /proxy endpoint to route requests through proxies.")
	})

	http.HandleFunc("/proxy", pool.proxyHandler)

	fmt.Println("Proxy server starting on :8080")
	fmt.Printf("Using proxies: %v\n", proxies)

	log.Fatal(http.ListenAndServe(":8080", nil))
}
