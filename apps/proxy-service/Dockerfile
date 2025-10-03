FROM golang:1.20-alpine AS gobuilder

# Install build dependencies
RUN apk add --no-cache git

WORKDIR /app

# Copy go.mod first for better layer caching
COPY go.mod ./
RUN go mod download

# Copy source code
COPY proxy.go .
COPY proxies.json .

# Build the binary with optimizations
RUN CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -ldflags="-s -w" -o proxy-service proxy.go

FROM alpine:latest

# Install ca-certificates for HTTPS requests and wget for health checks
RUN apk --no-cache add ca-certificates wget

WORKDIR /app

# Copy the binary and config from builder
COPY --from=gobuilder /app/proxy-service .
COPY --from=gobuilder /app/proxies.json .

# Set environment variable
ENV PROXY_SERVICE_PORT=8080

# Expose the port
EXPOSE ${PROXY_SERVICE_PORT}

# Run the service
CMD ["./proxy-service"]
