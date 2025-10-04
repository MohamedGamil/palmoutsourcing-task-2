/**
 * React Query Hooks for Product API
 * Provides hooks for all product CRUD operations with caching and state management
 */

'use client';

import {
  useQuery,
  useMutation,
  useQueryClient,
  UseQueryOptions,
  UseMutationOptions,
  QueryKey,
} from '@tanstack/react-query';
import productService from '@/services/product.service';
import type {
  Product,
  PaginatedResponse,
  ProductFilters,
  CreateProductRequest,
  UpdateProductRequest,
  ScrapeProductsRequest,
  ScrapeProductsResponse,
  ProductStats,
} from '@/types/api';

/**
 * Query Keys for React Query
 */
export const productKeys = {
  all: ['products'] as const,
  lists: () => [...productKeys.all, 'list'] as const,
  list: (filters?: ProductFilters) => [...productKeys.lists(), filters] as const,
  details: () => [...productKeys.all, 'detail'] as const,
  detail: (id: number) => [...productKeys.details(), id] as const,
  stats: () => [...productKeys.all, 'stats'] as const,
};

/**
 * Hook: Get paginated list of products with filters
 * @param filters - Optional filters for products
 * @param options - React Query options
 */
export const useProducts = (
  filters?: ProductFilters,
  options?: Omit<
    UseQueryOptions<PaginatedResponse<Product>, Error, PaginatedResponse<Product>, QueryKey>,
    'queryKey' | 'queryFn'
  >
) => {
  return useQuery({
    queryKey: productKeys.list(filters),
    queryFn: () => productService.getProducts(filters),
    staleTime: 30000, // Data considered fresh for 30 seconds
    gcTime: 5 * 60 * 1000, // Cache for 5 minutes (formerly cacheTime)
    ...options,
  });
};

/**
 * Hook: Get a single product by ID
 * @param id - Product ID
 * @param options - React Query options
 */
export const useProduct = (
  id: number,
  options?: Omit<
    UseQueryOptions<Product, Error, Product, QueryKey>,
    'queryKey' | 'queryFn'
  >
) => {
  return useQuery({
    queryKey: productKeys.detail(id),
    queryFn: () => productService.getProduct(id),
    enabled: !!id, // Only run query if id is truthy
    staleTime: 30000,
    gcTime: 5 * 60 * 1000,
    ...options,
  });
};

/**
 * Hook: Get product statistics
 * @param options - React Query options
 */
export const useProductStats = (
  options?: Omit<
    UseQueryOptions<ProductStats, Error, ProductStats, QueryKey>,
    'queryKey' | 'queryFn'
  >
) => {
  return useQuery({
    queryKey: productKeys.stats(),
    queryFn: () => productService.getStats(),
    staleTime: 60000, // Stats considered fresh for 1 minute
    gcTime: 10 * 60 * 1000, // Cache for 10 minutes
    ...options,
  });
};

/**
 * Hook: Create a new product (watch a product)
 * @param options - React Query mutation options
 */
export const useCreateProduct = (
  options?: UseMutationOptions<Product, Error, CreateProductRequest>
) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: CreateProductRequest) => productService.createProduct(data),
    onSuccess: (newProduct) => {
      // Invalidate product lists to refetch with new product
      queryClient.invalidateQueries({ queryKey: productKeys.lists() });

      // Invalidate stats
      queryClient.invalidateQueries({ queryKey: productKeys.stats() });

      // Optionally set the new product in cache
      queryClient.setQueryData(productKeys.detail(newProduct.id), newProduct);
    },
    ...options,
  });
};

/**
 * Hook: Update a product
 * @param options - React Query mutation options
 */
export const useUpdateProduct = (
  options?: UseMutationOptions<Product, Error, { id: number; data: UpdateProductRequest }>
) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateProductRequest }) =>
      productService.updateProduct(id, data),
    onSuccess: (updatedProduct, variables) => {
      // Update the product in cache
      queryClient.setQueryData(productKeys.detail(variables.id), updatedProduct);

      // Invalidate product lists to show updated data
      queryClient.invalidateQueries({ queryKey: productKeys.lists() });

      // Invalidate stats if active status changed
      if (variables.data.is_active !== undefined) {
        queryClient.invalidateQueries({ queryKey: productKeys.stats() });
      }
    },
    ...options,
  });
};

/**
 * Hook: Delete a product
 * @param options - React Query mutation options
 */
export const useDeleteProduct = (
  options?: UseMutationOptions<void, Error, number>
) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => productService.deleteProduct(id),
    onSuccess: (_, deletedId) => {
      // Remove product from cache
      queryClient.removeQueries({ queryKey: productKeys.detail(deletedId) });

      // Invalidate product lists
      queryClient.invalidateQueries({ queryKey: productKeys.lists() });

      // Invalidate stats
      queryClient.invalidateQueries({ queryKey: productKeys.stats() });
    },
    ...options,
  });
};

/**
 * Hook: Scrape products manually
 * @param options - React Query mutation options
 */
export const useScrapeProducts = (
  options?: UseMutationOptions<ScrapeProductsResponse, Error, ScrapeProductsRequest>
) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: ScrapeProductsRequest) => productService.scrapeProducts(data),
    onSuccess: (response, variables) => {
      // TODO: Fix invalidation
      // Invalidate affected products
      // variables.urls.forEach((url) => {
      //   queryClient.invalidateQueries({ queryKey: productKeys.detail(url) });
      // });

      // Invalidate product lists to show updated scrape data
      queryClient.invalidateQueries({ queryKey: productKeys.lists() });
    },
    ...options,
  });
};

/**
 * Hook: Rescrape a single product by URL
 * Forces update of existing product data
 * @param options - React Query mutation options
 */
export const useScrapeProduct = (
  options?: UseMutationOptions<Product, Error, string>
) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (url: string) => productService.scrapeProduct(url),
    onSuccess: (product) => {
      // Invalidate the updated product
      queryClient.invalidateQueries({ queryKey: productKeys.detail(product.id) });

      // Invalidate product lists to show updated data
      queryClient.invalidateQueries({ queryKey: productKeys.lists() });

      // Invalidate stats
      queryClient.invalidateQueries({ queryKey: productKeys.stats() });
    },
    ...options,
  });
};

/**
 * Hook: Prefetch product details
 * Useful for optimistic loading
 */
export const usePrefetchProduct = () => {
  const queryClient = useQueryClient();

  return (id: number) => {
    queryClient.prefetchQuery({
      queryKey: productKeys.detail(id),
      queryFn: () => productService.getProduct(id),
      staleTime: 30000,
    });
  };
};

/**
 * Hook: Optimistic update helper
 * Updates cache immediately before mutation completes
 */
export const useOptimisticProduct = () => {
  const queryClient = useQueryClient();

  return {
    updateProduct: (id: number, updates: Partial<Product>) => {
      queryClient.setQueryData<Product>(
        productKeys.detail(id),
        (old) => (old ? { ...old, ...updates } : old)
      );
    },
    rollbackProduct: (id: number) => {
      queryClient.invalidateQueries({ queryKey: productKeys.detail(id) });
    },
  };
};
