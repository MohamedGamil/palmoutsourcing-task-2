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
- Scrape product information from eCommerce websites
- Store product data in a relational database
- Provide RESTful API for data access
- Display products in a responsive web interface
- Manage proxy rotation for web scraping
- Auto-refresh product data

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
    - `title` (VARCHAR, NOT NULL)
    - `price` (DECIMAL(10,2), NOT NULL)
    - `image_url` (TEXT, NULLABLE)
    - `created_at` (TIMESTAMP)
    - `updated_at` (TIMESTAMP)

#### 3.1.3 Product Model
- **REQ-MODEL-001:** System SHALL create a Product Eloquent model
- **REQ-MODEL-002:** Product model SHALL include mass assignment protection
- **REQ-MODEL-003:** Product model SHALL define fillable attributes: title, price, image_url
- **REQ-MODEL-004:** Product model SHALL use timestamp fields

#### 3.1.4 Web Scraping Service
- **REQ-SCRAPE-001:** System SHALL implement a dedicated scraping service class
- **REQ-SCRAPE-002:** Service SHALL use Guzzle HTTP client for making requests
- **REQ-SCRAPE-003:** Service SHALL target eCommerce product pages (Amazon, Jumia, or similar for future extension and scalability)
- **REQ-SCRAPE-004:** Service SHALL extract: product title, price, and image URL
- **REQ-SCRAPE-005:** Service SHALL implement user-agent rotation from a predefined list
- **REQ-SCRAPE-006:** Service SHALL handle HTTP errors gracefully
- **REQ-SCRAPE-007:** Service SHALL validate scraped data before storage
- **REQ-SCRAPE-008:** Service SHALL store successfully scraped products in MySQL database
- **REQ-SCRAPE-009:** Service SHALL log scraping activities and errors

#### 3.1.5 API Endpoints
- **REQ-API-001:** System SHALL provide GET endpoint `/api/products`
- **REQ-API-002:** `/api/products` endpoint SHALL return JSON array of all products
- **REQ-API-003:** Response SHALL include: id, title, price, image_url, created_at
- **REQ-API-004:** API SHALL implement CORS headers for frontend access
- **REQ-API-005:** API SHALL return appropriate HTTP status codes
- **REQ-API-006:** API responses SHALL be paginated (optional enhancement)

#### 3.1.6 Integration with Golang Service
- **REQ-INT-001:** Backend SHALL communicate with Golang proxy service
- **REQ-INT-002:** System SHALL retrieve active proxies from Golang service
- **REQ-INT-003:** Integration SHALL handle proxy service unavailability

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
- **Backend:** PHP 8.x, Laravel 10.x, Guzzle, MySQL 8.x
- **Frontend:** Node.js, Next.js 13+, React 18+
- **Microservice:** Golang 1.20+
- **Development:** Composer, npm/yarn, Git

---

## 6. Data Requirements

### 6.1 Data Models

#### Product Entity
```
Product {
    id: integer (PK, auto-increment)
    title: string (max 255 characters)
    price: decimal(10,2)
    image_url: string (nullable, max 2048 characters)
    created_at: timestamp
    updated_at: timestamp
}
```

### 6.2 Data Validation
- Title: Required, string, max 255 characters
- Price: Required, numeric, positive value
- Image URL: Optional, valid URL format

---

## 7. Interface Requirements

### 7.1 API Specification

#### GET /api/products
**Response (200 OK):**
```json
{
    "data": [
        {
            "id": 1,
            "title": "Product Name",
            "price": "99.99",
            "image_url": "https://example.com/image.jpg",
            "created_at": "2024-01-01T12:00:00Z"
        }
    ]
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