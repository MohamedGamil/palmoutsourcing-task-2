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
    - `price_currency` (VARCHAR(3), DEFAULT 'USD') - ISO 4217 currency code
    - `rating` (DECIMAL(3,2), NULLABLE) - Product rating (0.00 to 5.00)
    - `rating_count` (INTEGER, DEFAULT 0) - Number of customer ratings
    - `image_url` (TEXT, NULLABLE)
    - `product_url` (TEXT, NOT NULL) - URL of the product being watched
    - `platform` (ENUM('amazon', 'jumia'), NOT NULL) - Source platform
    - `platform_id` (VARCHAR(255), NULLABLE) - Platform-specific product identifier/SKU
    - `platform_category` (VARCHAR(255), NULLABLE) - Category from platform
    - `last_scraped_at` (TIMESTAMP, NULLABLE) - Last successful scrape time
    - `scrape_count` (INTEGER, DEFAULT 0) - Number of times scraped
    - `is_active` (BOOLEAN, DEFAULT TRUE) - Whether product is being actively watched
    - `created_at` (TIMESTAMP)
    - `updated_at` (TIMESTAMP)
- **REQ-DB-002:** System SHALL create indexes on frequently queried fields (platform, is_active, platform_category, platform_id, created_at)
- **REQ-DB-003:** System SHALL ensure product_url is unique per platform to prevent duplicate watches
- **REQ-DB-004:** System SHALL ensure platform_id is unique per platform when provided

#### 3.1.3 Product Model
- **REQ-MODEL-001:** System SHALL create a Product Eloquent model
- **REQ-MODEL-002:** Product model SHALL include mass assignment protection
- **REQ-MODEL-003:** Product model SHALL define fillable attributes: title, price, price_currency, rating, rating_count, image_url, product_url, platform, platform_id, platform_category, last_scraped_at, scrape_count, is_active
- **REQ-MODEL-004:** Product model SHALL use timestamp fields (created_at, updated_at)
- **REQ-MODEL-005:** Product model SHALL cast platform as string enum
- **REQ-MODEL-006:** Product model SHALL cast is_active as boolean
- **REQ-MODEL-007:** Product model SHALL cast last_scraped_at as datetime
- **REQ-MODEL-008:** Product model SHALL implement scopes for filtering active products
- **REQ-MODEL-009:** Product model SHALL implement scopes for filtering by platform
- **REQ-MODEL-010:** Product model SHALL cast price, rating as decimal
- **REQ-MODEL-011:** Product model SHALL cast rating_count, scrape_count as integer
- **REQ-MODEL-012:** Product model SHALL validate rating range (0-5)
- **REQ-MODEL-013:** Product model SHALL validate price_currency as valid ISO 4217 code

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
- **REQ-SCRAPE-004:** Service SHALL extract: product title, price, price currency, rating, rating count, image URL, product URL, platform identifier/SKU, and platform category
- **REQ-SCRAPE-005:** Service SHALL implement user-agent rotation from a predefined list
- **REQ-SCRAPE-006:** Service SHALL handle HTTP errors gracefully
- **REQ-SCRAPE-007:** Service SHALL validate scraped data before storage
- **REQ-SCRAPE-008:** Service SHALL store successfully scraped products in MySQL database
- **REQ-SCRAPE-009:** Service SHALL log scraping activities and errors
- **REQ-SCRAPE-010:** Service SHALL support scraping a single product by URL
- **REQ-SCRAPE-011:** Service SHALL support scraping a list of products from multiple URLs
- **REQ-SCRAPE-012:** Service SHALL differentiate between Amazon and Jumia product page structures
- **REQ-SCRAPE-013:** Service SHALL handle platform-specific HTML parsing requirements
- **REQ-SCRAPE-014:** Service SHALL extract platform-specific category information
- **REQ-SCRAPE-015:** Service SHALL detect and extract currency from price information

#### 3.1.6 Automated Product Updates
- **REQ-AUTO-001:** System SHALL implement a job scheduler for periodic product updates
- **REQ-AUTO-002:** Scheduler SHALL automatically update product data at configurable intervals
- **REQ-AUTO-003:** Default update interval SHALL be configurable via environment variables
- **REQ-AUTO-004:** System SHALL use Laravel's task scheduling for job execution
- **REQ-AUTO-005:** Update jobs SHALL run in background without affecting API performance
- **REQ-AUTO-006:** System SHALL track last successful update time for each product
- **REQ-AUTO-007:** Failed update attempts SHALL be logged and retried
- **REQ-AUTO-008:** System SHALL support queue workers for processing multiple updates concurrently

#### 3.1.7 Product Mapping Service
- **REQ-MAP-001:** System SHALL implement a dedicated product mapping service
- **REQ-MAP-002:** Service SHALL transform scraped data into structured format
- **REQ-MAP-003:** Service SHALL validate and normalize product data
- **REQ-MAP-004:** Service SHALL apply business rules for data transformation
- **REQ-MAP-005:** Service SHALL handle mapping errors gracefully
- **REQ-MAP-006:** Service SHALL log mapping activities and errors
- **REQ-MAP-007:** Service SHALL support batch mapping of multiple products
- **REQ-MAP-008:** Service SHALL extract and normalize pricing information
- **REQ-MAP-009:** Service SHALL categorize products based on platform and content
- **REQ-MAP-010:** Service SHALL generate unique product identifiers per platform

#### 3.1.8 RESTful API Endpoints
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

#### 3.1.8 RESTful API Endpoints
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

#### 3.1.9 Filtering and Pagination
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
- **REQ-FILTER-011:** GET `/api/products` endpoint SHALL support filtering by rating range
- **REQ-FILTER-012:** GET `/api/products` endpoint SHALL support filtering by platform_category
- **REQ-FILTER-013:** GET `/api/products` endpoint SHALL support filtering by price_currency

#### 3.1.9 Filtering and Pagination
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
- **REQ-FILTER-011:** GET `/api/products` endpoint SHALL support filtering by rating range
- **REQ-FILTER-012:** GET `/api/products` endpoint SHALL support filtering by platform_category
- **REQ-FILTER-013:** GET `/api/products` endpoint SHALL support filtering by price_currency

#### 3.1.10 Repository Requirements
- **REQ-REPO-001:** Repository SHALL implement findById method to retrieve product by ID
- **REQ-REPO-002:** Repository SHALL implement save method to create or update products
- **REQ-REPO-003:** Repository SHALL implement delete method to remove products
- **REQ-REPO-004:** Repository SHALL implement findByUrl method to retrieve product by URL and platform
- **REQ-REPO-005:** Repository SHALL implement findAllActive method to retrieve active products
- **REQ-REPO-006:** Repository SHALL implement findProductsNeedingScraping method

#### 3.1.11 Data Validation Requirements
- **REQ-VAL-001:** Product URL SHALL be validated as required and valid URL format
- **REQ-VAL-002:** Product URL SHALL be validated to match Amazon or Jumia domain patterns
- **REQ-VAL-003:** Product title SHALL be required, string type, max 500 characters
- **REQ-VAL-004:** Product price SHALL be numeric, positive value, with max 2 decimal places
- **REQ-VAL-005:** Image URL SHALL be optional, valid URL format when provided
- **REQ-VAL-006:** Platform field SHALL accept only 'amazon' or 'jumia' values
- **REQ-VAL-007:** All input data SHALL be sanitized to prevent XSS attacks
- **REQ-VAL-008:** API SHALL return 400 Bad Request for malformed JSON
- **REQ-VAL-009:** API SHALL return 422 Unprocessable Entity for validation failures
- **REQ-VAL-010:** Rating SHALL be optional, numeric value between 0.00 and 5.00
- **REQ-VAL-011:** Rating count SHALL be optional, non-negative integer
- **REQ-VAL-012:** Price currency SHALL be optional, valid ISO 4217 currency code (3 characters)
- **REQ-VAL-013:** Platform category SHALL be optional, string type, max 255 characters
- **REQ-VAL-014:** Platform ID SHALL be optional, string type, max 255 characters

#### 3.1.11 Data Validation Requirements
- **REQ-VAL-001:** Product URL SHALL be validated as required and valid URL format
- **REQ-VAL-002:** Product URL SHALL be validated to match Amazon or Jumia domain patterns
- **REQ-VAL-003:** Product title SHALL be required, string type, max 500 characters
- **REQ-VAL-004:** Product price SHALL be numeric, positive value, with max 2 decimal places
- **REQ-VAL-005:** Image URL SHALL be optional, valid URL format when provided
- **REQ-VAL-006:** Platform field SHALL accept only 'amazon' or 'jumia' values
- **REQ-VAL-007:** All input data SHALL be sanitized to prevent XSS attacks
- **REQ-VAL-008:** API SHALL return 400 Bad Request for malformed JSON
- **REQ-VAL-009:** API SHALL return 422 Unprocessable Entity for validation failures
- **REQ-VAL-010:** Rating SHALL be optional, numeric value between 0.00 and 5.00
- **REQ-VAL-011:** Rating count SHALL be optional, non-negative integer
- **REQ-VAL-012:** Price currency SHALL be optional, valid ISO 4217 currency code (3 characters)
- **REQ-VAL-013:** Platform category SHALL be optional, string type, max 255 characters
- **REQ-VAL-014:** Platform ID SHALL be optional, string type, max 255 characters

#### 3.1.12 Integration with Golang Service
- **REQ-INT-001:** Backend SHALL communicate with Golang proxy service
- **REQ-INT-002:** System SHALL retrieve active proxies from Golang service
- **REQ-INT-003:** Integration SHALL handle proxy service unavailability
- **REQ-INT-004:** System SHALL use proxy rotation for all scraping requests
- **REQ-INT-005:** Failed proxy requests SHALL trigger fallback mechanisms

---

### 3.2 Golang Microservice Requirements

#### 3.2.1 Proxy Management
- **REQ-GO-001 (REQ-PROXY-001):** Service SHALL be written in Golang
- **REQ-GO-002 (REQ-PROXY-002):** Service SHALL maintain a pool of proxy servers
- **REQ-GO-003 (REQ-PROXY-003):** Service SHALL implement proxy rotation logic
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
- **REQ-PERF-004:** System SHALL implement caching for frequently accessed data to improve performance
- **REQ-PERF-005:** Database queries SHALL utilize proper indexing for optimal performance
- **REQ-PERF-006:** API responses SHALL be cached using Redis or similar caching mechanism
- **REQ-PERF-007:** Cache invalidation SHALL occur on data updates

### 4.2 Security
- **REQ-SEC-001:** API SHALL validate all input data
- **REQ-SEC-002:** System SHALL prevent SQL injection attacks
- **REQ-SEC-003:** Sensitive configuration SHALL use environment variables
- **REQ-SEC-004:** API SHALL implement CORS policy
- **REQ-SEC-005:** All environment variables SHALL be documented with examples
- **REQ-SEC-006:** Secrets and API keys SHALL never be committed to version control
- **REQ-SEC-007:** System SHALL implement input sanitization for all user inputs

### 4.3 Reliability
- **REQ-REL-001:** System SHALL handle network failures gracefully
- **REQ-REL-002:** Database connections SHALL use connection pooling
- **REQ-REL-003:** Services SHALL implement comprehensive error logging
- **REQ-REL-004:** System SHALL log significant events for monitoring and debugging
- **REQ-REL-005:** Error logs SHALL include timestamp, context, and stack trace
- **REQ-REL-006:** System SHALL implement retry mechanisms for failed operations
- **REQ-REL-007:** Critical errors SHALL be logged with appropriate severity levels

### 4.4 Maintainability
- **REQ-MAINT-001:** Code SHALL follow PSR-12 standards (PHP)
- **REQ-MAINT-002:** Code SHALL include inline documentation
- **REQ-MAINT-003:** Project SHALL include README with setup instructions
- **REQ-MAINT-004:** Configuration SHALL be externalized using environment variables
- **REQ-MAINT-005:** System SHALL maintain clear separation of concerns between domain and app layers
- **REQ-MAINT-006:** Domain layer SHALL remain independent of any framework or external libraries
- **REQ-MAINT-007:** Business logic SHALL be isolated from implementation details
- **REQ-MAINT-008:** Code SHALL facilitate easier testing and maintenance
- **REQ-MAINT-009:** System SHALL generate comprehensive API documentation using OpenAPI/Swagger
- **REQ-MAINT-010:** API documentation SHALL be automatically generated from code annotations
- **REQ-MAINT-011:** Documentation SHALL be accessible via dedicated API documentation endpoint

### 4.5 Scalability
- **REQ-SCALE-001:** Database schema SHALL support indexing
- **REQ-SCALE-002:** API SHALL support pagination
- **REQ-SCALE-003:** Proxy pool SHALL be expandable
- **REQ-SCALE-004:** Application SHALL be designed to handle increased load
- **REQ-SCALE-005:** System SHALL support horizontal scaling through stateless design
- **REQ-SCALE-006:** Background jobs SHALL use queue system for asynchronous processing
- **REQ-SCALE-007:** Domain and app layers SHALL evolve independently
- **REQ-SCALE-008:** Architecture SHALL support microservices extraction if needed

### 4.6 Architecture and Design Principles
- **REQ-ARCH-001:** System SHALL implement Domain-Driven Design (DDD) principles
- **REQ-ARCH-002:** Domain layer SHALL contain abstract definitions of business logic
- **REQ-ARCH-003:** Domain layer SHALL include entities and value types
- **REQ-ARCH-004:** App layer SHALL contain concrete implementations of business logic
- **REQ-ARCH-005:** App layer SHALL implement repositories for data access based on domain layer
- **REQ-ARCH-006:** App layer SHALL implement services based on domain layer contracts
- **REQ-ARCH-007:** App layer SHALL implement use-cases for application logic
- **REQ-ARCH-008:** Controllers SHALL utilize use-cases and remain thin
- **REQ-ARCH-009:** Request classes SHALL handle HTTP request validation and authorization
- **REQ-ARCH-010:** Resource classes SHALL handle HTTP response formatting
- **REQ-ARCH-011:** Jobs SHALL handle background processing tasks
- **REQ-ARCH-012:** Policies SHALL implement authorization logic
- **REQ-ARCH-013:** Custom validation rules SHALL be implemented as separate Rule classes
- **REQ-ARCH-014:** Custom exceptions SHALL be implemented for domain-specific errors
- **REQ-ARCH-015:** Service providers SHALL configure app layer dependencies

### 4.7 Testability
- **REQ-TEST-001:** Domain logic SHALL be testable in isolation without framework dependencies
- **REQ-TEST-002:** Business logic isolation SHALL facilitate unit testing
- **REQ-TEST-003:** Use-cases SHALL be testable independently of HTTP layer
- **REQ-TEST-004:** Repositories SHALL use interfaces for easy mocking in tests
- **REQ-TEST-005:** Services SHALL use dependency injection for testability

### 4.8 Reusability
- **REQ-REUSE-001:** Domain logic SHALL be reusable across different parts of the application
- **REQ-REUSE-002:** Domain logic SHALL be portable to different applications
- **REQ-REUSE-003:** Business rules SHALL be centralized and not duplicated
- **REQ-REUSE-004:** Common functionality SHALL be abstracted into reusable components

### 4.9 Configuration Management
- **REQ-CONFIG-001:** All configuration SHALL use environment variables
- **REQ-CONFIG-002:** System SHALL provide .env.example file with all required variables
- **REQ-CONFIG-003:** Configuration variables SHALL be documented with purpose and format
- **REQ-CONFIG-004:** Default values SHALL be provided for non-sensitive configuration
- **REQ-CONFIG-005:** Environment-specific settings SHALL be clearly separated

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

### 5.2 Backend Architectural Layers

The Laravel backend follows a Domain-Driven Design (DDD) approach with clear separation between domain and application layers:

#### 5.2.1 Domain Layer (Framework-Independent)
The domain layer contains abstract definitions of business logic and is completely independent of Laravel or any external framework.

**Components:**
- **Entities:** Core business objects (e.g., Product entity with business rules)
- **Value Objects:** Immutable objects representing domain concepts (e.g., Price, ProductUrl, Platform)
- **Repository Interfaces:** Contracts defining data access operations
- **Service Interfaces:** Contracts for domain services
- **Domain Events:** Events representing business occurrences
- **Domain Exceptions:** Business rule violations

**Characteristics:**
- No framework dependencies
- Pure PHP classes and interfaces
- Contains only business logic
- Highly testable in isolation
- Reusable across different applications

#### 5.2.2 Application Layer (Framework-Specific)
The application layer provides concrete implementations and orchestrates the domain layer using Laravel framework.

**Components:**

**a) Repositories**
- Concrete implementations of domain repository interfaces
- Uses Eloquent ORM for data persistence
- Translates between domain entities and database models
- Example: `EloquentProductRepository implements ProductRepositoryInterface`

**b) Services**
- Concrete implementations of domain service interfaces
- Coordinates multiple repositories or external services
- Example: `ProductScrapingService implements ScrapingServiceInterface`

**c) Use Cases**
- Application-specific business logic
- Orchestrates domain entities, services, and repositories
- Implements specific user stories or features
- Examples: `CreateProductUseCase`, `UpdateProductPriceUseCase`, `BulkScrapeProductsUseCase`

**d) Controllers**
- HTTP request handlers
- Thin layer that delegates to use cases
- Handles HTTP-specific concerns (request/response)
- Does not contain business logic

**e) Request Classes (Form Requests)**
- HTTP request validation
- Authorization logic
- Input sanitization
- Example: `CreateProductRequest`, `UpdateProductRequest`

**f) Resources (API Resources)**
- HTTP response formatting
- Data transformation for API responses
- Consistent JSON structure
- Example: `ProductResource`, `ProductCollection`

**g) Jobs**
- Background processing tasks
- Asynchronous operations
- Queue-based processing
- Example: `ScrapeProductJob`, `UpdateProductPricesJob`

**h) Policies**
- Authorization logic
- Permission checks
- Access control rules
- Example: `ProductPolicy`

**i) Rules**
- Custom validation rules
- Reusable validation logic
- Example: `ValidAmazonUrl`, `ValidJumiaUrl`

**j) Exceptions**
- Custom exception handling
- Application-specific errors
- Example: `ProductNotFoundException`, `ScrapingFailedException`

**k) Providers (Service Providers)**
- Dependency injection configuration
- Service binding
- Application bootstrapping
- Example: `DomainServiceProvider`, `RepositoryServiceProvider`

#### 5.2.3 Layer Interaction Flow

```
HTTP Request
     ↓
Controller (thin)
     ↓
Use Case (orchestration)
     ↓
Domain Service (business logic)
     ↓
Repository (data access)
     ↓
Database
```

**Example Flow: Creating a Product**
1. **Controller** receives HTTP request
2. **Request** validates and authorizes input
3. **Controller** calls **Use Case** (CreateProductUseCase)
4. **Use Case** uses **Domain Service** (ScrapingService) to scrape product
5. **Domain Service** uses **Repository** to check for duplicates
6. **Use Case** creates **Domain Entity** (Product)
7. **Repository** persists entity to database
8. **Controller** returns **Resource** formatted response

### 5.3 Technology Stack
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
    price_currency: string (3 characters, default 'USD')
    rating: decimal(3,2) (nullable, 0.00-5.00)
    rating_count: integer (default 0)
    image_url: string (nullable, max 2048 characters)
    product_url: string (required, max 2048 characters)
    platform: enum('amazon', 'jumia')
    platform_id: string (nullable, max 255 characters)
    platform_category: string (nullable, max 255 characters)
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
- Price Currency: Optional, string, 3 characters (ISO 4217), default 'USD'
- Rating: Optional, numeric, 0.00 to 5.00, max 2 decimal places
- Rating Count: Optional, integer, non-negative, default 0
- Image URL: Optional, valid URL format, max 2048 characters
- Product URL: Required, valid URL format, must match Amazon or Jumia domain
- Platform: Required, must be 'amazon' or 'jumia'
- Platform ID: Optional, string, max 255 characters
- Platform Category: Optional, string, max 255 characters
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
- `min_rating` (decimal, optional): Minimum rating filter (0.00-5.00)
- `max_rating` (decimal, optional): Maximum rating filter (0.00-5.00)
- `category` (string, optional): Filter by platform_category
- `platform_id` (string, optional): Filter by platform_id
- `currency` (string, optional): Filter by price_currency
- `is_active` (boolean, optional): Filter by active status

**Response (200 OK):**
```json
{
    "data": [
        {
            "id": 1,
            "title": "Product Name",
            "price": "99.99",
            "price_currency": "USD",
            "rating": "4.50",
            "rating_count": 1250,
            "image_url": "https://example.com/image.jpg",
            "product_url": "https://www.amazon.com/product/...",
            "platform": "amazon",
            "platform_id": "B08N5WRWNW",
            "platform_category": "Electronics",
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
        "price_currency": "USD",
        "rating": "4.50",
        "rating_count": 1250,
        "image_url": "https://example.com/image.jpg",
        "product_url": "https://www.amazon.com/product/...",
        "platform": "amazon",
        "platform_id": "B08N5WRWNW",
        "platform_category": "Electronics",
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
        "price_currency": "USD",
        "rating": "4.50",
        "rating_count": 1250,
        "image_url": "https://example.com/image.jpg",
        "product_url": "https://www.amazon.com/product/...",
        "platform": "amazon",
        "platform_id": "B08N5WRWNW",
        "platform_category": "Electronics",
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
        "price_currency": "USD",
        "rating": "4.50",
        "rating_count": 1250,
        "image_url": "https://example.com/image.jpg",
        "product_url": "https://www.amazon.com/product/...",
        "platform": "amazon",
        "platform_id": "B08N5WRWNW",
        "platform_category": "Electronics",
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
- **REQ-DOC-001:** README.md with comprehensive setup instructions
- **REQ-DOC-002:** Installation steps for each component (Laravel, Next.js, Golang)
- **REQ-DOC-003:** OpenAPI/Swagger documentation for all API endpoints
- **REQ-DOC-004:** Swagger UI accessible at `/api/docs` endpoint
- **REQ-DOC-005:** Environment variable documentation with examples
- **REQ-DOC-006:** Architecture documentation explaining domain and app layers
- **REQ-DOC-007:** Code comments and inline documentation
- **REQ-DOC-008:** Database schema documentation
- **REQ-DOC-009:** API authentication and authorization guide
- **REQ-DOC-010:** Deployment and configuration guide

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

### 10.1 Domain Layer Testing
- **REQ-TEST-001:** Unit tests for domain entities (Product entity)
- **REQ-TEST-002:** Unit tests for value objects (Price, ProductUrl, Platform)
- **REQ-TEST-003:** Tests for domain business rules and validation
- **REQ-TEST-004:** Domain tests SHALL be independent of framework
- **REQ-TEST-005:** Domain tests SHALL not require database or external dependencies

### 10.2 Application Layer Testing
- **REQ-TEST-006:** Unit tests for use cases with mocked dependencies
- **REQ-TEST-007:** Unit tests for repositories with in-memory or test database
- **REQ-TEST-008:** Unit tests for services with mocked external dependencies
- **REQ-TEST-009:** Integration tests for repository implementations
- **REQ-TEST-010:** Tests for custom validation rules
- **REQ-TEST-011:** Tests for custom exceptions handling

### 10.3 API Testing
- **REQ-TEST-012:** Feature tests for all API endpoints
- **REQ-TEST-013:** Tests for request validation (CreateProductRequest, UpdateProductRequest)
- **REQ-TEST-014:** Tests for API resource formatting (ProductResource)
- **REQ-TEST-015:** Tests for authentication and authorization (Policies)
- **REQ-TEST-016:** Tests for error responses and status codes
- **REQ-TEST-017:** Tests for pagination and filtering

### 10.4 Background Jobs Testing
- **REQ-TEST-018:** Unit tests for job classes (ScrapeProductJob)
- **REQ-TEST-019:** Tests for job failure handling and retry logic
- **REQ-TEST-020:** Tests for scheduled tasks

### 10.5 Frontend Testing
- **REQ-TEST-021:** Component rendering tests
- **REQ-TEST-022:** API integration tests
- **REQ-TEST-023:** User interaction tests

### 10.6 Golang Service Testing
- **REQ-TEST-024:** Unit tests for proxy rotation logic
- **REQ-TEST-025:** API endpoint tests
- **REQ-TEST-026:** Concurrent request handling tests

### 10.7 Testing Standards
- **REQ-TEST-027:** Code coverage SHALL be minimum 70% for critical paths
- **REQ-TEST-028:** All tests SHALL be automated and runnable via CI/CD
- **REQ-TEST-029:** Tests SHALL follow AAA pattern (Arrange, Act, Assert)
- **REQ-TEST-030:** Test names SHALL clearly describe what is being tested

---

## 11. Logging and Monitoring Requirements

### 11.1 Event Logging
- **REQ-LOG-001:** System SHALL log all significant application events
- **REQ-LOG-002:** Logs SHALL include timestamp, severity level, and context
- **REQ-LOG-003:** Log levels SHALL include: DEBUG, INFO, WARNING, ERROR, CRITICAL
- **REQ-LOG-004:** Product creation, update, and deletion events SHALL be logged
- **REQ-LOG-005:** Scraping attempts (success and failure) SHALL be logged
- **REQ-LOG-006:** Background job execution SHALL be logged
- **REQ-LOG-007:** API request/response cycles SHALL be logged (configurable)

### 11.2 Error Logging
- **REQ-LOG-008:** All errors and exceptions SHALL be logged with full stack trace
- **REQ-LOG-009:** HTTP errors (4xx, 5xx) SHALL be logged with request details
- **REQ-LOG-010:** Database errors SHALL be logged with query context
- **REQ-LOG-011:** External service failures (scraping, proxy service) SHALL be logged
- **REQ-LOG-012:** Validation errors SHALL be logged at appropriate level
- **REQ-LOG-013:** Authentication and authorization failures SHALL be logged

### 11.3 Performance Monitoring
- **REQ-MON-001:** Slow queries (>100ms) SHALL be logged
- **REQ-MON-002:** API response times SHALL be tracked and logged
- **REQ-MON-003:** Scraping duration SHALL be tracked per product
- **REQ-MON-004:** Queue processing times SHALL be monitored
- **REQ-MON-005:** Cache hit/miss rates SHALL be tracked

### 11.4 Business Metrics Logging
- **REQ-LOG-014:** Product watch count SHALL be tracked
- **REQ-LOG-015:** Successful vs failed scraping attempts SHALL be logged
- **REQ-LOG-016:** Platform distribution (Amazon vs Jumia) SHALL be tracked
- **REQ-LOG-017:** Active vs inactive products SHALL be monitored

### 11.5 Log Management
- **REQ-LOG-018:** Logs SHALL be stored in structured format (JSON recommended)
- **REQ-LOG-019:** Log rotation SHALL be configured to prevent disk space issues
- **REQ-LOG-020:** Logs SHALL be retained for minimum 30 days
- **REQ-LOG-021:** Critical logs SHALL be easily searchable and filterable
- **REQ-LOG-022:** Log files SHALL be organized by date and severity

### 11.6 Debugging Support
- **REQ-DEBUG-001:** Development environment SHALL support detailed debug logging
- **REQ-DEBUG-002:** Debug mode SHALL log request/response payloads
- **REQ-DEBUG-003:** Database query logging SHALL be available in debug mode
- **REQ-DEBUG-004:** Debug logs SHALL NOT be enabled in production by default

---

## 12. Installation and Setup

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