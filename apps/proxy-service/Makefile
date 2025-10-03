.PHONY: build clean run start restart docker-build docker-run docker-stop docker-logs test

# Build the proxy service
build:
	go build -o bin/proxy-service proxy.go

# Run the proxy service
run:
	go run proxy.go

# Clean build artifacts
clean:
	killall proxy-service || true
	rm -rf bin/

# Build and run
start: build
	./bin/proxy-service

# Restart the service
restart: clean start

# Docker commands
docker-build:
	docker build -t proxy-service:latest .

docker-run:
	docker run -d --name proxy-service -p 7001:8080 -e PROXY_SERVICE_PORT=8080 proxy-service:latest

docker-stop:
	docker stop proxy-service || true
	docker rm proxy-service || true

docker-logs:
	docker logs -f proxy-service

docker-restart: docker-stop docker-build docker-run

# Test the service
test:
	@echo "Testing service endpoints..."
	@curl -s http://localhost:7001/ | python3 -m json.tool || echo "Service not running on port 7001"
	@echo "\nTesting /proxy/next endpoint..."
	@curl -s http://localhost:7001/proxy/next | python3 -m json.tool || echo "Failed"
	@echo "\nTesting /health endpoint..."
	@curl -s http://localhost:7001/health | python3 -m json.tool || echo "Failed"
