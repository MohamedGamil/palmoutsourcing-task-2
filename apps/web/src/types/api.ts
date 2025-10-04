/**
 * API Response Types
 * Based on the Laravel API specification
 */

export type Platform = 'amazon' | 'jumia';

export interface Product {
  id: number;
  title: string;
  price: string;
  price_currency: string;
  rating: string | null;
  rating_count: number;
  image_url: string | null;
  product_url: string;
  platform: Platform;
  platform_id: string | null;
  platform_category: string | null;
  last_scraped_at: string | null;
  scrape_count: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: PaginationMeta;
}

export interface PaginatedAPIResponse {
  data: Product[];
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number;
    to: number;
  };
}

export interface SingleResponse<T> {
  data: T;
  message?: string;
}

export interface MessageResponse {
  message: string;
}

export interface ValidationError {
  message: string;
  errors: Record<string, string[]>;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

export interface ProductStats {
  total_products: number;
  active_products: number;
  inactive_products: number;

  by_platform: {
    amazon: number;
    jumia: number;
  };

  price_stats: {
    min: number;
    max: number;
    avg: number;
  };

  rating_stats: {
    min: number;
    max: number;
    avg: number;
  };

  scraping_stats: {
    total_scrapes: number;
    avg_scrapes_per_product: number;
    products_never_scraped: number;
    products_scraped_today: number;
  };
}

// Request types
export interface ProductFilters {
  page?: number;
  per_page?: number;
  platform?: Platform;
  search?: string;
  min_price?: number;
  max_price?: number;
  min_rating?: number;
  max_rating?: number;
  category?: string;
  platform_id?: string;
  currency?: string;
  is_active?: boolean;
}

export interface CreateProductRequest {
  url: string;
}

export interface UpdateProductRequest {
  is_active?: boolean;
  // Add other updatable fields as needed
}

export interface ScrapeProductsRequest {
  urls: string[];
}

export interface ScrapeProductsResponse {
  message: string;
  results: {
    successful: number;
    failed: number;
    details: Array<{
      product_id: number;
      status: 'success' | 'failed';
      error?: string;
    }>;
  };
}
