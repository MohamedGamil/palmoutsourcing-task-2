/**
 * Product API Service
 * Handles all product-related API calls using the API client
 */

import { api } from '@/lib/api-client';
import type {
  Product,
  PaginatedResponse,
  SingleResponse,
  MessageResponse,
  ProductFilters,
  CreateProductRequest,
  UpdateProductRequest,
  ScrapeProductsRequest,
  ScrapeProductsResponse,
  ProductStats,
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
    const response = await api.get<PaginatedResponse<Product>>(`/products${queryString}`);
    return response.data;
  },

  /**
   * Get a single product by ID
   */
  async getProduct(id: number): Promise<Product> {
    const response = await api.get<SingleResponse<Product>>(`/products/${id}`);
    return response.data.data;
  },

  /**
   * Create a new product (watch a product URL)
   */
  async createProduct(data: CreateProductRequest): Promise<Product> {
    const response = await api.post<SingleResponse<Product>>('/products', data);
    return response.data.data;
  },

  /**
   * Update a product
   */
  async updateProduct(id: number, data: UpdateProductRequest): Promise<Product> {
    const response = await api.patch<SingleResponse<Product>>(`/products/${id}`, data);
    return response.data.data;
  },

  /**
   * Delete a product
   */
  async deleteProduct(id: number): Promise<MessageResponse> {
    const response = await api.delete<MessageResponse>(`/products/${id}`);
    return response.data;
  },

  /**
   * Manually trigger scraping for products
   */
  async scrapeProducts(data: ScrapeProductsRequest): Promise<ScrapeProductsResponse> {
    const response = await api.post<ScrapeProductsResponse>('/products/scrape', data);
    return response.data;
  },

  /**
   * Get product statistics
   * Note: This endpoint might need to be implemented in the Laravel backend
   */
  async getStats(): Promise<ProductStats> {
    const response = await api.get<SingleResponse<ProductStats>>('/products/stats');
    return response.data.data;
  },
};

export default productService;
