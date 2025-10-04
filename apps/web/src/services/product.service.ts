/**
 * Product API Service
 * Handles all product-related API calls using the API client
 */

import { api } from '@/lib/api-client';
import type {
  Product,
  PaginatedResponse,
  ProductFilters,
  CreateProductRequest,
  UpdateProductRequest,
  ScrapeProductsRequest,
  ScrapeProductsResponse,
  ProductStats,
  PaginatedAPIResponse,
} from '@/types/api';

/**
 * Build query string from filters
 */
const buildQueryString = (filters: ProductFilters = {}): string => {
  const params = new URLSearchParams();

  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      params.append(key, String(value));
    }
  });

  const queryString = params.toString();
  return queryString ? `?${queryString}` : '';
};

/**
 * Product API Service
 */
export const productService = {
  /**
   * Get paginated list of products with optional filters
   */
  async getProducts(filters?: ProductFilters): Promise<PaginatedResponse<Product>> {
    const queryString = buildQueryString(filters);
    const response = await api.get<Product[]>(`/products${queryString}`);
    const paginatedResponse = response as unknown as PaginatedAPIResponse;
    
    // Construct the paginated response from the API response structure
    return {
      data: paginatedResponse.data,
      meta: paginatedResponse.meta || {
        current_page: 1,
        per_page: 15,
        total: 0,
        last_page: 1,
        from: 0,
        to: 0,
      },
    };
  },

  /**
   * Get a single product by ID
   */
  async getProduct(id: number): Promise<Product> {
    // Laravel returns: { success, message, data: Product, ... }
    const response = await api.get<Product>(`/products/${id}`);
    return response.data;
  },

  /**
   * Create a new product (watch a product URL)
   */
  async createProduct(data: CreateProductRequest): Promise<Product> {
    // Laravel returns: { success, message, data: Product, ... }
    const response = await api.post<Product>('/products', data);
    return response.data;
  },

  /**
   * Update a product
   */
  async updateProduct(id: number, data: UpdateProductRequest): Promise<Product> {
    // Laravel returns: { success, message, data: Product, ... }
    const response = await api.patch<Product>(`/products/${id}`, data);
    return response.data;
  },

  /**
   * Delete a product
   */
  async deleteProduct(id: number): Promise<void> {
    // Laravel returns: { success, message, data: null, ... }
    await api.delete(`/products/${id}`);
    // No return value needed for delete
  },

  /**
   * Manually trigger scraping for products
   */
  async scrapeProducts(data: ScrapeProductsRequest): Promise<ScrapeProductsResponse> {
    // Laravel returns: { success, message, data: ScrapeProductsResponse, ... }
    const response = await api.post<ScrapeProductsResponse>('/products/scrape', data);
    return response.data;
  },

  /**
   * Get product statistics
   */
  async getStats(): Promise<ProductStats> {
    // Laravel returns: { success, message, data: ProductStats, ... }
    const response = await api.get<ProductStats>('/products/statistics');
    return response.data;
  },
};

export default productService;
