# API Quick Reference

Quick reference guide for all API endpoints.

---

## Product Endpoints

### List Products
```http
GET /api/products
```
**Query Params:** `page`, `per_page`, `platform`, `search`, `min_price`, `max_price`, `min_rating`, `max_rating`, `category`, `currency`, `is_active`, `sort_by`, `sort_order`, `created_after`, `created_before`

**Example:**
```bash
curl "http://localhost/api/products?platform=amazon&per_page=20&sort_by=price&sort_order=asc"
```

---

### Get Product
```http
GET /api/products/{id}
```

**Example:**
```bash
curl "http://localhost/api/products/123"
```

---

### Create Product
```http
POST /api/products
Content-Type: application/json

{
  "url": "https://www.amazon.com/dp/B001",
  "platform": "amazon"
}
```

**Example:**
```bash
curl -X POST "http://localhost/api/products" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://www.amazon.com/dp/B001","platform":"amazon"}'
```

---

### Update Product
```http
PATCH /api/products/{id}
Content-Type: application/json

{
  "rescrape": true,
  "is_active": false
}
```

**Example - Re-scrape:**
```bash
curl -X PATCH "http://localhost/api/products/123" \
  -H "Content-Type: application/json" \
  -d '{"rescrape":true}'
```

**Example - Toggle Active:**
```bash
curl -X PATCH "http://localhost/api/products/123" \
  -H "Content-Type: application/json" \
  -d '{"is_active":false}'
```

---

### Delete Product
```http
DELETE /api/products/{id}
```

**Example:**
```bash
curl -X DELETE "http://localhost/api/products/123"
```

---

### Get Statistics
```http
GET /api/products/statistics
```

**Example:**
```bash
curl "http://localhost/api/products/statistics"
```

---

## Scraping Endpoints

### Scrape Single Product
```http
POST /api/scraping/scrape
Content-Type: application/json

{
  "url": "https://www.amazon.com/dp/B001",
  "platform": "amazon"
}
```

**Example:**
```bash
curl -X POST "http://localhost/api/scraping/scrape" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://www.amazon.com/dp/B001","platform":"amazon"}'
```

---

### Batch Scrape Products
```http
POST /api/scraping/batch
Content-Type: application/json

{
  "products": [
    {"url": "https://www.amazon.com/dp/B001", "platform": "amazon"},
    {"url": "https://www.jumia.com.eg/product-123", "platform": "jumia"}
  ]
}
```

**Example:**
```bash
curl -X POST "http://localhost/api/scraping/batch" \
  -H "Content-Type: application/json" \
  -d '{
    "products": [
      {"url":"https://www.amazon.com/dp/B001","platform":"amazon"},
      {"url":"https://www.jumia.com.eg/product-123","platform":"jumia"}
    ]
  }'
```

**Limits:** Min 1 product, Max 50 products

---

### Trigger Re-scraping
```http
POST /api/scraping/trigger/{id}
```

**Example:**
```bash
curl -X POST "http://localhost/api/scraping/trigger/123"
```

---

### Get Supported Platforms
```http
GET /api/scraping/platforms
```

**Example:**
```bash
curl "http://localhost/api/scraping/platforms"
```

**Response:**
```json
{
  "success": true,
  "message": "Supported platforms retrieved successfully",
  "data": {
    "platforms": ["amazon", "jumia"]
  }
}
```

---

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {...}
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": [...]
}
```

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "url": ["The url field is required."],
    "platform": ["The selected platform is invalid."]
  }
}
```

---

## HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | Successful GET, PUT, PATCH, DELETE |
| 201 | Created | Successful POST creating resource |
| 400 | Bad Request | Invalid request parameters |
| 404 | Not Found | Resource doesn't exist |
| 409 | Conflict | Resource already exists |
| 422 | Unprocessable Entity | Validation or business logic error |
| 500 | Internal Server Error | Unexpected error |

---

## Common Filters

### Platform Filter
```
?platform=amazon
?platform=jumia
```

### Search Filter
```
?search=laptop
```

### Price Filters
```
?min_price=50.00
?max_price=500.00
?min_price=50&max_price=500
```

### Rating Filters
```
?min_rating=4.0
?max_rating=5.0
?min_rating=4&max_rating=5
```

### Date Filters
```
?created_after=2024-01-01
?created_before=2024-12-31
```

### Active Status Filter
```
?is_active=true
?is_active=false
```

### Category Filter
```
?category=Electronics
```

### Currency Filter
```
?currency=USD
?currency=EGP
```

### Sorting
```
?sort_by=price&sort_order=asc
?sort_by=rating&sort_order=desc
?sort_by=created_at&sort_order=desc
```

**Sort Fields:** `created_at`, `updated_at`, `price`, `rating`, `title`

### Pagination
```
?page=1&per_page=15
?page=2&per_page=50
```

**Limits:** `page` >= 1, `per_page` 1-100

### Combined Example
```bash
curl "http://localhost/api/products?\
platform=amazon&\
min_price=50&\
max_price=500&\
min_rating=4&\
search=laptop&\
is_active=true&\
sort_by=price&\
sort_order=asc&\
page=1&\
per_page=20"
```

---

## Testing with curl

### Create and List
```bash
# Create product
curl -X POST "http://localhost/api/products" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://www.amazon.com/dp/B001","platform":"amazon"}'

# List all products
curl "http://localhost/api/products"
```

### Update Workflow
```bash
# Get product
curl "http://localhost/api/products/123"

# Re-scrape product
curl -X PATCH "http://localhost/api/products/123" \
  -H "Content-Type: application/json" \
  -d '{"rescrape":true}'

# Deactivate product
curl -X PATCH "http://localhost/api/products/123" \
  -H "Content-Type: application/json" \
  -d '{"is_active":false}'
```

### Batch Operations
```bash
# Batch scrape
curl -X POST "http://localhost/api/scraping/batch" \
  -H "Content-Type: application/json" \
  -d @products.json

# products.json:
{
  "products": [
    {"url":"https://www.amazon.com/dp/B001","platform":"amazon"},
    {"url":"https://www.amazon.com/dp/B002","platform":"amazon"},
    {"url":"https://www.jumia.com.eg/product-123","platform":"jumia"}
  ]
}
```

---

## Error Handling Examples

### 404 Not Found
```bash
curl "http://localhost/api/products/99999"
```
```json
{
  "success": false,
  "message": "Product not found",
  "errors": [...]
}
```

### 409 Conflict
```bash
# Try creating duplicate product
curl -X POST "http://localhost/api/products" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://www.amazon.com/dp/B001","platform":"amazon"}'
```
```json
{
  "success": false,
  "message": "Product already exists",
  "errors": [...]
}
```

### 422 Validation Error
```bash
# Missing required field
curl -X POST "http://localhost/api/products" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://www.amazon.com/dp/B001"}'
```
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "platform": ["The platform field is required."]
  }
}
```

---

## Postman Collection

Import this into Postman:

```json
{
  "info": {
    "name": "Product Scraping API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Products",
      "item": [
        {
          "name": "List Products",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/api/products?page=1&per_page=15"
          }
        },
        {
          "name": "Get Product",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/api/products/1"
          }
        },
        {
          "name": "Create Product",
          "request": {
            "method": "POST",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "url": "{{base_url}}/api/products",
            "body": {
              "mode": "raw",
              "raw": "{\"url\":\"https://www.amazon.com/dp/B001\",\"platform\":\"amazon\"}"
            }
          }
        },
        {
          "name": "Update Product",
          "request": {
            "method": "PATCH",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "url": "{{base_url}}/api/products/1",
            "body": {
              "mode": "raw",
              "raw": "{\"rescrape\":true}"
            }
          }
        },
        {
          "name": "Delete Product",
          "request": {
            "method": "DELETE",
            "url": "{{base_url}}/api/products/1"
          }
        },
        {
          "name": "Get Statistics",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/api/products/statistics"
          }
        }
      ]
    },
    {
      "name": "Scraping",
      "item": [
        {
          "name": "Scrape Product",
          "request": {
            "method": "POST",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "url": "{{base_url}}/api/scraping/scrape",
            "body": {
              "mode": "raw",
              "raw": "{\"url\":\"https://www.amazon.com/dp/B001\",\"platform\":\"amazon\"}"
            }
          }
        },
        {
          "name": "Batch Scrape",
          "request": {
            "method": "POST",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "url": "{{base_url}}/api/scraping/batch",
            "body": {
              "mode": "raw",
              "raw": "{\"products\":[{\"url\":\"https://www.amazon.com/dp/B001\",\"platform\":\"amazon\"}]}"
            }
          }
        },
        {
          "name": "Trigger Re-scrape",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/scraping/trigger/1"
          }
        },
        {
          "name": "Get Platforms",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/api/scraping/platforms"
          }
        }
      ]
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost"
    }
  ]
}
```
