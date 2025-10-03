# Software Requirements Specification (SRS)
## Web Scraping Service with Laravel, Next.js, and Golang

### Document Information
- **Project Name:** Web Scraping Service
- **Version:** 1.0
- **Date:** 03-10-2025
- **Author:** Mohamed Gamil

---

## 1. Introduction

### 1.1 Purpose
This document specifies the functional and non-functional requirements for a web scraping service that extracts product information from eCommerce websites (Amazon and Jumia) and displays it through a web interface.

### 1.2 Scope
The system consists of three main components:
- **Backend API (Laravel/PHP)** - Manages data persistence and scraping logic
- **Frontend Application (Next.js/React)** - Provides user interface for viewing products
- **Proxy Management Service (Golang)** - Handles proxy rotation for scraping requests

### 1.3 Definitions and Acronyms
- **API:** Application Programming Interface
- **SPA:** Single Page Application
- **ORM:** Object-Relational Mapping
- **HTTP:** Hypertext Transfer Protocol
- **JSON:** JavaScript Object Notation

---

## 2. Overall Description

### 2.1 Product Perspective
The system is a standalone web application that scrapes product data from eCommerce platforms (Amazon and Jumia) and presents it to users in a responsive interface.

### 2.2 Product Functions
- Watch and manage products from Amazon and Jumia e-commerce platforms
- Scrape product information from stored product URLs
- Store and update product data in a relational database
- Provide comprehensive RESTful API with CRUD operations
- Support filtering and pagination for efficient data retrieval
- Automatically update product data at regular intervals using job scheduler
- Display products in a responsive web interface
- Manage proxy rotation for web scraping
- Validate and sanitize all input data
- Handle errors gracefully with appropriate error messages

### 2.3 User Characteristics
- **End Users:** Individuals seeking to view aggregated product information
- **System Administrators:** Technical staff managing the application infrastructure

---

## 3. System Features and Requirements

### 3.1 Backend Requirements (Laravel/PHP)

#### 3.1.1 Project Setup
- **REQ-BE-001:** System SHALL be built using Laravel framework (latest stable version)
- **REQ-BE-002:** System SHALL use MySQL as the primary database
- **REQ-BE-003:** System SHALL follow MVC architectural pattern
- **REQ-BE-003:** System SHALL follow SOLID principles
- **REQ-BE-004:** System SHALL use Laravel Eloquent ORM for database operations

#### 3.1.2 Database Schema
- **REQ-DB-001:** System SHALL implement a `products` table with the following fields:
    - `id` (Primary Key, Auto-increment)
    - `title` (VARCHAR(500), NOT NULL)
    - `price` (DECIMAL(10,2), NOT NULL)
    - `image_url` (TEXT, NULLABLE)
    - `product_url` (TEXT, NOT NULL) - URL of the product being watched
    - `platform` (ENUM('amazon', 'jumia'), NOT NULL) - Source platform
    - `last_scraped_at` (TIMESTAMP, NULLABLE) - Last successful scrape time
    - `scrape_count` (INTEGER, DEFAULT 0) - Number of times scraped
    - `is_active` (BOOLEAN, DEFAULT TRUE) - Whether product is being actively watched
    - `created_at` (TIMESTAMP)
    - `updated_at` (TIMESTAMP)
- **REQ-DB-002:** System SHALL create indexes on frequently queried fields (platform, is_active, created_at)
- **REQ-DB-003:** System SHALL ensure product_url is unique per platform to prevent duplicate watches

#### 3.1.3 Product Model
- **REQ-MODEL-001:** System SHALL create a Product Eloquent model
- **REQ-MODEL-002:** Product model SHALL include mass assignment protection
- **REQ-MODEL-003:** Product model SHALL define fillable attributes: title, price, image_url, product_url, platform, last_scraped_at, scrape_count, is_active
- **REQ-MODEL-004:** Product model SHALL use timestamp fields (created_at, updated_at)
- **REQ-MODEL-005:** Product model SHALL cast platform as string enum
- **REQ-MODEL-006:** Product model SHALL cast is_active as boolean
- **REQ-MODEL-007:** Product model SHALL cast last_scraped_at as datetime
- **REQ-MODEL-008:** Product model SHALL implement scopes for filtering active products
- **REQ-MODEL-009:** Product model SHALL implement scopes for filtering by platform

#### 3.1.4 Product Watching and Management
- **REQ-WATCH-001:** System SHALL support watching and managing products from Amazon and Jumia e-commerce platforms
- **REQ-WATCH-002:** Watching a product SHALL involve storing its URL in the database
- **REQ-WATCH-003:** System SHALL periodically check watched products for updates
- **REQ-WATCH-004:** Each watched product SHALL maintain its source platform (Amazon or Jumia)
- **REQ-WATCH-005:** System SHALL track the last update timestamp for each product
- **REQ-WATCH-006:** System SHALL support multiple products being watched simultaneously

#### 3.1.5 Web Scraping Service
- **REQ-SCRAPE-001:** System SHALL implement a dedicated scraping service class
- **REQ-SCRAPE-002:** Service SHALL use Guzzle HTTP client for making requests
- **REQ-SCRAPE-003:** Service SHALL support scraping from Amazon and Jumia platforms
- **REQ-SCRAPE-004:** Service SHALL extract: product title, price, image URL, and product URL
- **REQ-SCRAPE-005:** Service SHALL implement user-agent rotation from a predefined list
- **REQ-SCRAPE-006:** Service SHALL handle HTTP errors gracefully
- **REQ-SCRAPE-007:** Service SHALL validate scraped data before storage
- **REQ-SCRAPE-008:** Service SHALL store successfully scraped products in MySQL database
- **REQ-SCRAPE-009:** Service SHALL log scraping activities and errors
- **REQ-SCRAPE-010:** Service SHALL support scraping a single product by URL
- **REQ-SCRAPE-011:** Service SHALL support scraping a list of products from multiple URLs
- **REQ-SCRAPE-012:** Service SHALL differentiate between Amazon and Jumia product page structures
- **REQ-SCRAPE-013:** Service SHALL handle platform-specific HTML parsing requirements

#### 3.1.6 Automated Product Updates
- **REQ-AUTO-001:** System SHALL implement a job scheduler for periodic product updates
- **REQ-AUTO-002:** Scheduler SHALL automatically update product data at configurable intervals
- **REQ-AUTO-003:** Default update interval SHALL be configurable via environment variables
- **REQ-AUTO-004:** System SHALL use Laravel's task scheduling for job execution
- **REQ-AUTO-005:** Update jobs SHALL run in background without affecting API performance
- **REQ-AUTO-006:** System SHALL track last successful update time for each product
- **REQ-AUTO-007:** Failed update attempts SHALL be logged and retried
- **REQ-AUTO-008:** System SHALL support queue workers for processing multiple updates concurrently

#### 3.1.7 RESTful API Endpoints
- **REQ-API-001:** System SHALL provide comprehensive CRUD endpoints for product management
- **REQ-API-002:** System SHALL implement GET endpoint `/api/products` for retrieving products
- **REQ-API-003:** System SHALL implement POST endpoint `/api/products` for creating new watched products
- **REQ-API-004:** System SHALL implement GET endpoint `/api/products/{id}` for retrieving a single product
- **REQ-API-005:** System SHALL implement PUT/PATCH endpoint `/api/products/{id}` for updating products
- **REQ-API-006:** System SHALL implement DELETE endpoint `/api/products/{id}` for removing watched products
- **REQ-API-007:** All endpoints SHALL implement proper data validation
- **REQ-API-008:** All endpoints SHALL implement comprehensive error handling
- **REQ-API-009:** Error responses SHALL include descriptive error messages and appropriate HTTP status codes
- **REQ-API-010:** Validation errors SHALL return 422 status code with detailed field-level errors

#### 3.1.8 Filtering and Pagination
- **REQ-FILTER-001:** GET `/api/products` endpoint SHALL support filtering by platform (Amazon/Jumia)
- **REQ-FILTER-002:** GET `/api/products` endpoint SHALL support filtering by price range
- **REQ-FILTER-003:** GET `/api/products` endpoint SHALL support filtering by product title (search)
- **REQ-FILTER-004:** GET `/api/products` endpoint SHALL support filtering by date range
- **REQ-FILTER-005:** System SHALL implement cursor-based or offset-based pagination
- **REQ-FILTER-006:** Pagination SHALL include metadata: total count, current page, per_page, last_page
- **REQ-FILTER-007:** Default page size SHALL be configurable (recommended: 15-50 items)
- **REQ-FILTER-008:** System SHALL support custom page size via query parameter
- **REQ-FILTER-009:** Filtering and pagination SHALL work together seamlessly
- **REQ-FILTER-010:** API responses SHALL follow consistent JSON structure for paginated data

#### 3.1.9 Data Validation Requirements
- **REQ-VAL-001:** Product URL SHALL be validated as required and valid URL format
- **REQ-VAL-002:** Product URL SHALL be validated to match Amazon or Jumia domain patterns
- **REQ-VAL-003:** Product title SHALL be required, string type, max 500 characters
- **REQ-VAL-004:** Product price SHALL be numeric, positive value, with max 2 decimal places
- **REQ-VAL-005:** Image URL SHALL be optional, valid URL format when provided
- **REQ-VAL-006:** Platform field SHALL accept only 'amazon' or 'jumia' values
- **REQ-VAL-007:** All input data SHALL be sanitized to prevent XSS attacks
- **REQ-VAL-008:** API SHALL return 400 Bad Request for malformed JSON
- **REQ-VAL-009:** API SHALL return 422 Unprocessable Entity for validation failures

#### 3.1.10 Integration with Golang Service
- **REQ-INT-001:** Backend SHALL communicate with Golang proxy service
- **REQ-INT-002:** System SHALL retrieve active proxies from Golang service
- **REQ-INT-003:** Integration SHALL handle proxy service unavailability
- **REQ-INT-004:** System SHALL use proxy rotation for all scraping requests
- **REQ-INT-005:** Failed proxy requests SHALL trigger fallback mechanisms

---

### 3.2 Golang Microservice Requirements

#### 3.2.1 Proxy Management
- **REQ-GO-001:** Service SHALL be written in Golang
- **REQ-GO-002:** Service SHALL maintain a pool of proxy servers
- **REQ-GO-003:** Service SHALL implement proxy rotation logic
- **REQ-GO-004:** Service SHALL validate proxy availability before rotation
- **REQ-GO-005:** Service SHALL expose HTTP endpoint for proxy retrieval
- **REQ-GO-006:** Service SHALL return proxy in format: `host:port`
- **REQ-GO-007:** Service SHALL handle concurrent proxy requests
- **REQ-GO-008:** Service SHALL remove non-functional proxies from pool
- **REQ-GO-009:** Service SHALL log proxy usage and rotation events

#### 3.2.2 API Interface
- **REQ-GO-API-001:** Service SHALL expose endpoint `/proxy/next`
- **REQ-GO-API-002:** Service SHALL return JSON response with proxy details
- **REQ-GO-API-003:** Service SHALL implement rate limiting
- **REQ-GO-API-004:** Service SHALL run on configurable port

---

### 3.3 Frontend Requirements (Next.js/React)

#### 3.3.1 Project Setup
- **REQ-FE-001:** Application SHALL be built using Next.js framework
- **REQ-FE-002:** Application SHALL use React for UI components
- **REQ-FE-003:** Application SHALL use TypeScript (recommended) or JavaScript
- **REQ-FE-004:** Application SHALL implement responsive design

#### 3.3.2 Products Page
- **REQ-PAGE-001:** System SHALL implement `/products` route
- **REQ-PAGE-002:** Page SHALL fetch data from Laravel API endpoint
- **REQ-PAGE-003:** Page SHALL display products in a grid layout
- **REQ-PAGE-004:** Each product card SHALL display:
    - Product image
    - Product title
    - Product price
    - Created date (optional)
- **REQ-PAGE-005:** Grid SHALL be responsive (mobile, tablet, desktop)
- **REQ-PAGE-006:** Page SHALL handle loading states
- **REQ-PAGE-007:** Page SHALL handle error states
- **REQ-PAGE-008:** Page SHALL display message when no products available

#### 3.3.3 Auto-Refresh
- **REQ-REFRESH-001:** Products page SHALL auto-refresh every 30 seconds
- **REQ-REFRESH-002:** Refresh SHALL fetch latest data from API
- **REQ-REFRESH-003:** Refresh SHALL not disrupt user experience
- **REQ-REFRESH-004:** User SHALL be able to manually refresh (optional)

#### 3.3.4 Styling and UX
- **REQ-UI-001:** Application SHALL use CSS modules or styled-components
- **REQ-UI-002:** Design SHALL be clean and modern
- **REQ-UI-003:** Images SHALL have loading states
- **REQ-UI-004:** Grid SHALL adapt to different screen sizes:
    - Mobile: 1 column
    - Tablet: 2-3 columns
    - Desktop: 3-4 columns

---

## 4. Non-Functional Requirements

### 4.1 Performance
- **REQ-PERF-001:** API response time SHALL be under 500ms for product listing
- **REQ-PERF-002:** Frontend page load time SHALL be under 2 seconds
- **REQ-PERF-003:** Scraping service SHALL handle rate limiting

### 4.2 Security
- **REQ-SEC-001:** API SHALL validate all input data
- **REQ-SEC-002:** System SHALL prevent SQL injection attacks
- **REQ-SEC-003:** Sensitive configuration SHALL use environment variables
- **REQ-SEC-004:** API SHALL implement CORS policy

### 4.3 Reliability
- **REQ-REL-001:** System SHALL handle network failures gracefully
- **REQ-REL-002:** Database connections SHALL use connection pooling
- **REQ-REL-003:** Services SHALL implement error logging

### 4.4 Maintainability
- **REQ-MAINT-001:** Code SHALL follow PSR-12 standards (PHP)
- **REQ-MAINT-002:** Code SHALL include inline documentation
- **REQ-MAINT-003:** Project SHALL include README with setup instructions
- **REQ-MAINT-004:** Configuration SHALL be externalized

### 4.5 Scalability
- **REQ-SCALE-001:** Database schema SHALL support indexing
- **REQ-SCALE-002:** API SHALL support pagination
- **REQ-SCALE-003:** Proxy pool SHALL be expandable

---

## 5. System Architecture

### 5.1 Component Diagram
```
┌─────────────────┐
│   Next.js App   │
│   (Frontend)    │
└────────┬────────┘
                 │ HTTP/REST
                 ↓
┌─────────────────┐      ┌──────────────┐
│  Laravel API    │←────→│   MySQL DB   │
│   (Backend)     │      └──────────────┘
└────────┬────────┘
                 │ HTTP
                 ↓
┌─────────────────┐
│  Golang Proxy   │
│    Service      │
└─────────────────┘
```

### 5.2 Technology Stack
- **Backend:** PHP 8.x, Laravel 10.x, Guzzle, MySQL 8.x, Laravel Task Scheduler, Laravel Queues
- **Frontend:** Node.js, Next.js 13+, React 18+
- **Microservice:** Golang 1.20+
- **Job Processing:** Laravel Scheduler (Cron), Queue Workers (Database/Redis driver)
- **Development:** Composer, npm/yarn, Git

---

## 6. Data Requirements

### 6.1 Data Models

#### Product Entity
```
Product {
    id: integer (PK, auto-increment)
    title: string (max 500 characters)
    price: decimal(10,2)
    image_url: string (nullable, max 2048 characters)
    product_url: string (required, max 2048 characters)
    platform: enum('amazon', 'jumia')
    last_scraped_at: timestamp (nullable)
    scrape_count: integer (default 0)
    is_active: boolean (default true)
    created_at: timestamp
    updated_at: timestamp
}
```

### 6.2 Data Validation
- Title: Required, string, max 500 characters
- Price: Required, numeric, positive value, max 2 decimal places
- Image URL: Optional, valid URL format, max 2048 characters
- Product URL: Required, valid URL format, must match Amazon or Jumia domain
- Platform: Required, must be 'amazon' or 'jumia'
- Is Active: Boolean, default true

---

## 7. Interface Requirements

### 7.1 API Specification

#### GET /api/products
**Description:** Retrieve a paginated list of products with optional filtering

**Query Parameters:**
- `page` (integer, optional): Page number (default: 1)
- `per_page` (integer, optional): Items per page (default: 15)
- `platform` (string, optional): Filter by platform ('amazon' or 'jumia')
- `search` (string, optional): Search in product title
- `min_price` (decimal, optional): Minimum price filter
- `max_price` (decimal, optional): Maximum price filter
- `is_active` (boolean, optional): Filter by active status

**Response (200 OK):**
```json
{
    "data": [
        {
            "id": 1,
            "title": "Product Name",
            "price": "99.99",
            "image_url": "https://example.com/image.jpg",
            "product_url": "https://www.amazon.com/product/...",
            "platform": "amazon",
            "last_scraped_at": "2025-10-03T12:00:00Z",
            "scrape_count": 5,
            "is_active": true,
            "created_at": "2025-10-01T12:00:00Z",
            "updated_at": "2025-10-03T12:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7
    }
}
```

#### POST /api/products
**Description:** Create a new product to watch

**Request Body:**
```json
{
    "product_url": "https://www.amazon.com/product/...",
    "platform": "amazon"
}
```

**Response (201 Created):**
```json
{
    "data": {
        "id": 1,
        "title": "Product Name",
        "price": "99.99",
        "image_url": "https://example.com/image.jpg",
        "product_url": "https://www.amazon.com/product/...",
        "platform": "amazon",
        "last_scraped_at": "2025-10-03T12:00:00Z",
        "scrape_count": 1,
        "is_active": true,
        "created_at": "2025-10-03T12:00:00Z",
        "updated_at": "2025-10-03T12:00:00Z"
    },
    "message": "Product added and scraped successfully"
}
```

**Response (422 Unprocessable Entity):**
```json
{
    "message": "Validation failed",
    "errors": {
        "product_url": ["The product URL must be a valid Amazon or Jumia URL"],
        "platform": ["The platform field is required"]
    }
}
```

#### GET /api/products/{id}
**Description:** Retrieve a single product by ID

**Response (200 OK):**
```json
{
    "data": {
        "id": 1,
        "title": "Product Name",
        "price": "99.99",
        "image_url": "https://example.com/image.jpg",
        "product_url": "https://www.amazon.com/product/...",
        "platform": "amazon",
        "last_scraped_at": "2025-10-03T12:00:00Z",
        "scrape_count": 5,
        "is_active": true,
        "created_at": "2025-10-01T12:00:00Z",
        "updated_at": "2025-10-03T12:00:00Z"
    }
}
```

**Response (404 Not Found):**
```json
{
    "message": "Product not found"
}
```

#### PUT/PATCH /api/products/{id}
**Description:** Update a product (trigger re-scrape or update settings)

**Request Body:**
```json
{
    "is_active": false
}
```

**Response (200 OK):**
```json
{
    "data": {
        "id": 1,
        "title": "Product Name",
        "price": "99.99",
        "image_url": "https://example.com/image.jpg",
        "product_url": "https://www.amazon.com/product/...",
        "platform": "amazon",
        "last_scraped_at": "2025-10-03T12:00:00Z",
        "scrape_count": 5,
        "is_active": false,
        "created_at": "2025-10-01T12:00:00Z",
        "updated_at": "2025-10-03T13:00:00Z"
    },
    "message": "Product updated successfully"
}
```

#### DELETE /api/products/{id}
**Description:** Delete a watched product

**Response (200 OK):**
```json
{
    "message": "Product deleted successfully"
}
```

**Response (404 Not Found):**
```json
{
    "message": "Product not found"
}
```

#### POST /api/products/scrape
**Description:** Manually trigger scraping for one or more products

**Request Body:**
```json
{
    "product_ids": [1, 2, 3]
}
```

**Response (200 OK):**
```json
{
    "message": "Scraping initiated for 3 products",
    "results": {
        "successful": 2,
        "failed": 1,
        "details": [
            {"product_id": 1, "status": "success"},
            {"product_id": 2, "status": "success"},
            {"product_id": 3, "status": "failed", "error": "Product not found"}
        ]
    }
}
```

### 7.2 Golang Proxy Service API

#### GET /proxy/next
**Response (200 OK):**
```json
{
    "proxy": "123.456.789.0:8080",
    "protocol": "http"
}
```

---

## 8. Development Constraints

### 8.1 Technical Constraints
- PHP version: 8.0 or higher
- Node.js version: 16.x or higher
- MySQL version: 8.0 or higher
- Golang version: 1.20 or higher

### 8.2 Development Standards
- Version control: Git
- Dependency management: Composer (PHP), npm/yarn (Node.js), Go modules
- Code style: PSR-12 (PHP), ESLint (JavaScript)

---

## 9. Deliverables

### 9.1 Code Deliverables
- **REQ-DEL-001:** Complete Laravel backend source code
- **REQ-DEL-002:** Complete Next.js frontend source code
- **REQ-DEL-003:** Golang proxy management script/service
- **REQ-DEL-004:** Database migration files
- **REQ-DEL-005:** Environment configuration examples

### 9.2 Documentation Deliverables
- **REQ-DOC-001:** README.md with setup instructions
- **REQ-DOC-002:** Installation steps for each component
- **REQ-DOC-003:** API documentation
- **REQ-DOC-004:** Environment variable documentation

### 9.3 Submission Format
- **REQ-SUB-001:** GitHub repository OR ZIP file
- **REQ-SUB-002:** Clear folder structure separating components
- **REQ-SUB-003:** One-minute voice note explaining:
    - System architecture overview
    - Key design decisions
    - Integration approach (Laravel + Next.js + Golang)
    - Challenges and solutions

---

## 10. Testing Requirements

### 10.1 Backend Testing
- **REQ-TEST-001:** Unit tests for Product model
- **REQ-TEST-002:** Integration tests for API endpoints
- **REQ-TEST-003:** Tests for scraping service

### 10.2 Frontend Testing
- **REQ-TEST-004:** Component rendering tests
- **REQ-TEST-005:** API integration tests

### 10.3 Golang Service Testing
- **REQ-TEST-006:** Unit tests for proxy rotation logic
- **REQ-TEST-007:** API endpoint tests

---

## 11. Installation and Setup

### 11.1 Prerequisites
- PHP 8.x with Composer
- Node.js 16+ with npm/yarn
- MySQL 8.x
- Golang 1.20+
- Git

### 11.2 Setup Steps (High-Level)
1. Clone repository
2. Configure environment variables
3. Install dependencies (Composer, npm, Go modules)
4. Run database migrations
5. Start services (Laravel, Next.js dev server, Golang service)
6. Access application

---

## 12. Appendices

### 12.1 User-Agent Strings (Example)
```
Mozilla/5.0 (Windows NT 10.0; Win64; x64)...
Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)...
Mozilla/5.0 (X11; Linux x86_64)...
```

### 12.2 Sample eCommerce Target Sites
- Amazon product pages
- Jumia product pages
- Other ethical scraping targets with robots.txt compliance

### 12.3 Glossary
- **Web Scraping:** Automated extraction of data from websites
- **Proxy Rotation:** Technique of using multiple IP addresses to distribute requests
- **User-Agent:** HTTP header identifying the client application
- **RESTful API:** Web service following REST architectural principles

---

**End of Software Requirements Specification**