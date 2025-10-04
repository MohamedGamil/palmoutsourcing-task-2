/**
 * Product API Module
 * Centralized exports for product-related API functionality
 */

// Hooks
export {
  useProducts,
  useProduct,
  useProductStats,
  useCreateProduct,
  useUpdateProduct,
  useDeleteProduct,
  useScrapeProducts,
  useScrapeProduct,
  usePrefetchProduct,
  useOptimisticProduct,
  productKeys,
} from '@/hooks/use-products';

// Services
export { default as productService } from '@/services/product.service';

// Types
export type {
  Product,
  Platform,
  PaginatedResponse,
  PaginationMeta,
  SingleResponse,
  MessageResponse,
  ValidationError,
  ApiError,
  ProductStats,
  ProductFilters,
  CreateProductRequest,
  UpdateProductRequest,
  ScrapeProductsRequest,
  ScrapeProductsResponse,
} from '@/types/api';
