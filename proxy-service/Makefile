.PHONY: build clean run start restart

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
