# React Query Integration Documentation

## Overview
This document explains the React Query setup for managing product data in the Next.js frontend application.

## Architecture

### Files Structure
```
src/
├── types/
│   └── api.ts                      # TypeScript types for API
├── lib/
│   └── api-client.ts               # API client configuration
├── services/
│   └── product.service.ts          # Product API service functions
├── hooks/
│   └── use-products.ts             # React Query hooks
└── components/
    └── ReactQueryProvider.tsx      # React Query provider
```

## Components

### 1. TypeScript Types (`src/types/api.ts`)

Defines all TypeScript interfaces for:
- **Product**: Complete product model matching Laravel API
- **PaginatedResponse**: Paginated list response
- **ProductFilters**: Query parameters for filtering
- **CreateProductRequest**: Request payload for creating products
- **UpdateProductRequest**: Request payload for updating products
- **ProductStats**: Statistics response
- **ApiError**: Error response structure

### 2. Product Service (`src/services/product.service.ts`)

Service layer that wraps API calls:

```typescript
productService.getProducts(filters)      // GET /api/products
productService.getProduct(id)            // GET /api/products/:id
productService.createProduct(data)       // POST /api/products
productService.updateProduct(id, data)   // PATCH /api/products/:id
productService.deleteProduct(id)         // DELETE /api/products/:id
productService.scrapeProducts(data)      // POST /api/products/scrape
productService.getStats()                // GET /api/products/stats
```

### 3. React Query Hooks (`src/hooks/use-products.ts`)

#### Available Hooks

##### `useProducts(filters?, options?)`
Fetch paginated list of products with optional filters.

**Example:**
```typescript
const { data, isLoading, error, refetch } = useProducts({
  platform: 'amazon',
  page: 1,
  per_page: 20,
  search: 'laptop',
  min_price: 100,
  max_price: 1000,
});
```

**Returns:**
```typescript
{
  data: PaginatedResponse<Product> | undefined
  isLoading: boolean
  isError: boolean
  error: Error | null
  refetch: () => void
  // ... other React Query properties
}
```

##### `useProduct(id, options?)`
Fetch a single product by ID.

**Example:**
```typescript
const { data: product, isLoading } = useProduct(123);
```

##### `useProductStats(options?)`
Fetch product statistics.

**Example:**
```typescript
const { data: stats } = useProductStats();
// stats: { total_products, active_products, platforms, etc. }
```

##### `useCreateProduct(options?)`
Create a new product (watch a product URL).

**Example:**
```typescript
const createProduct = useCreateProduct({
  onSuccess: (product) => {
    console.log('Product created:', product);
  },
  onError: (error) => {
    console.error('Failed to create:', error);
  },
});

// Usage
createProduct.mutate({
  product_url: 'https://www.amazon.com/...',
  platform: 'amazon',
});
```

**Mutation object properties:**
```typescript
{
  mutate: (data: CreateProductRequest) => void
  mutateAsync: (data: CreateProductRequest) => Promise<Product>
  isLoading: boolean
  isSuccess: boolean
  isError: boolean
  error: Error | null
  data: Product | undefined
  reset: () => void
}
```

##### `useUpdateProduct(options?)`
Update a product.

**Example:**
```typescript
const updateProduct = useUpdateProduct({
  onSuccess: () => {
    toast.success('Product updated!');
  },
});

// Usage
updateProduct.mutate({
  id: 123,
  data: { is_active: false },
});
```

##### `useDeleteProduct(options?)`
Delete a product.

**Example:**
```typescript
const deleteProduct = useDeleteProduct({
  onSuccess: () => {
    toast.success('Product deleted!');
  },
});

// Usage
deleteProduct.mutate(123); // product ID
```

##### `useScrapeProducts(options?)`
Manually trigger scraping for products.

**Example:**
```typescript
const scrapeProducts = useScrapeProducts({
  onSuccess: (response) => {
    console.log('Scraping results:', response);
  },
});

// Usage
scrapeProducts.mutate({
  product_ids: [1, 2, 3],
});
```

##### `usePrefetchProduct()`
Prefetch product details for optimistic loading.

**Example:**
```typescript
const prefetchProduct = usePrefetchProduct();

// Prefetch on hover
<div onMouseEnter={() => prefetchProduct(123)}>
  View Product
</div>
```

##### `useOptimisticProduct()`
Helper for optimistic UI updates.

**Example:**
```typescript
const { updateProduct, rollbackProduct } = useOptimisticProduct();

// Optimistically update UI
updateProduct(123, { is_active: false });

// Rollback if needed
rollbackProduct(123);
```

## Usage Examples

### Example 1: Product List Component

```tsx
'use client';

import { useProducts } from '@/hooks/use-products';
import { useState } from 'react';

export default function ProductList() {
  const [page, setPage] = useState(1);
  const [platform, setPlatform] = useState<'amazon' | 'jumia' | undefined>();

  const { data, isLoading, error } = useProducts({
    page,
    per_page: 20,
    platform,
  });

  if (isLoading) return <div>Loading products...</div>;
  if (error) return <div>Error: {error.message}</div>;

  return (
    <div>
      {/* Filters */}
      <select onChange={(e) => setPlatform(e.target.value as any)}>
        <option value="">All Platforms</option>
        <option value="amazon">Amazon</option>
        <option value="jumia">Jumia</option>
      </select>

      {/* Products Grid */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {data?.data.map((product) => (
          <div key={product.id} className="border p-4 rounded">
            <h3>{product.title}</h3>
            <p>${product.price}</p>
            <span>{product.platform}</span>
          </div>
        ))}
      </div>

      {/* Pagination */}
      <div className="flex gap-2 mt-4">
        <button
          onClick={() => setPage(p => Math.max(1, p - 1))}
          disabled={page === 1}
        >
          Previous
        </button>
        <span>Page {page} of {data?.meta.last_page}</span>
        <button
          onClick={() => setPage(p => p + 1)}
          disabled={page === data?.meta.last_page}
        >
          Next
        </button>
      </div>
    </div>
  );
}
```

### Example 2: Create Product Form

```tsx
'use client';

import { useCreateProduct } from '@/hooks/use-products';
import { useState } from 'react';

export default function CreateProductForm() {
  const [url, setUrl] = useState('');
  const createProduct = useCreateProduct({
    onSuccess: (product) => {
      alert(`Product created: ${product.title}`);
      setUrl('');
    },
    onError: (error) => {
      alert(`Error: ${error.message}`);
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createProduct.mutate({ product_url: url });
  };

  return (
    <form onSubmit={handleSubmit}>
      <input
        type="url"
        value={url}
        onChange={(e) => setUrl(e.target.value)}
        placeholder="Enter product URL"
        required
      />
      <button type="submit" disabled={createProduct.isLoading}>
        {createProduct.isLoading ? 'Creating...' : 'Watch Product'}
      </button>
      {createProduct.isError && (
        <p className="text-red-500">{createProduct.error.message}</p>
      )}
    </form>
  );
}
```

### Example 3: Product Details with Actions

```tsx
'use client';

import { useProduct, useUpdateProduct, useDeleteProduct } from '@/hooks/use-products';
import { useRouter } from 'next/navigation';

export default function ProductDetails({ id }: { id: number }) {
  const router = useRouter();
  const { data: product, isLoading } = useProduct(id);
  const updateProduct = useUpdateProduct();
  const deleteProduct = useDeleteProduct({
    onSuccess: () => {
      router.push('/products');
    },
  });

  if (isLoading) return <div>Loading...</div>;
  if (!product) return <div>Product not found</div>;

  return (
    <div>
      <h1>{product.title}</h1>
      <p>Price: ${product.price} {product.price_currency}</p>
      <p>Platform: {product.platform}</p>
      <p>Status: {product.is_active ? 'Active' : 'Inactive'}</p>

      <div className="flex gap-2">
        <button
          onClick={() => updateProduct.mutate({
            id,
            data: { is_active: !product.is_active }
          })}
        >
          {product.is_active ? 'Deactivate' : 'Activate'}
        </button>

        <button
          onClick={() => {
            if (confirm('Delete this product?')) {
              deleteProduct.mutate(id);
            }
          }}
          className="text-red-500"
        >
          Delete
        </button>
      </div>
    </div>
  );
}
```

### Example 4: Stats Dashboard

```tsx
'use client';

import { useProductStats } from '@/hooks/use-products';

export default function StatsDashboard() {
  const { data: stats, isLoading } = useProductStats();

  if (isLoading) return <div>Loading stats...</div>;

  return (
    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div className="stat-card">
        <h3>Total Products</h3>
        <p className="text-3xl">{stats?.total_products}</p>
      </div>
      <div className="stat-card">
        <h3>Active</h3>
        <p className="text-3xl">{stats?.active_products}</p>
      </div>
      <div className="stat-card">
        <h3>Amazon</h3>
        <p className="text-3xl">{stats?.platforms.amazon}</p>
      </div>
      <div className="stat-card">
        <h3>Jumia</h3>
        <p className="text-3xl">{stats?.platforms.jumia}</p>
      </div>
    </div>
  );
}
```

## Caching Strategy

### Query Keys
React Query uses query keys to identify and cache queries:

```typescript
productKeys.all                    // ['products']
productKeys.lists()                // ['products', 'list']
productKeys.list(filters)          // ['products', 'list', filters]
productKeys.details()              // ['products', 'detail']
productKeys.detail(id)             // ['products', 'detail', id]
productKeys.stats()                // ['products', 'stats']
```

### Cache Invalidation

Mutations automatically invalidate related queries:

- **Create Product**: Invalidates product lists and stats
- **Update Product**: Invalidates specific product and lists
- **Delete Product**: Removes product from cache, invalidates lists and stats
- **Scrape Products**: Invalidates affected products and lists

### Manual Cache Control

```typescript
import { useQueryClient } from '@tanstack/react-query';
import { productKeys } from '@/hooks/use-products';

const queryClient = useQueryClient();

// Invalidate all products
queryClient.invalidateQueries({ queryKey: productKeys.all });

// Invalidate specific product
queryClient.invalidateQueries({ queryKey: productKeys.detail(123) });

// Set query data manually
queryClient.setQueryData(productKeys.detail(123), updatedProduct);

// Prefetch
queryClient.prefetchQuery({
  queryKey: productKeys.detail(123),
  queryFn: () => productService.getProduct(123),
});
```

## Configuration

### Environment Variables

Create `.env.local`:
```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

### Query Client Options

Configured in `ReactQueryProvider.tsx`:
```typescript
{
  staleTime: 30000,          // 30 seconds
  gcTime: 5 * 60 * 1000,     // 5 minutes cache
  retry: 1,                   // Retry once on failure
  refetchOnWindowFocus: true, // Refetch on focus
  refetchOnReconnect: true,   // Refetch on reconnect
}
```

## React Query Devtools

Development tools are automatically enabled in development mode:
- Access via bottom-right button
- View queries, mutations, and cache
- Debug refetch behavior
- Inspect query data

## Error Handling

### Global Error Handler
```typescript
// In mutation options
onError: (error) => {
  console.error('Mutation error:', error);
  // Add toast notification here
}
```

### Component-Level Error Handling
```typescript
const { error, isError } = useProducts();

if (isError) {
  return <div>Error: {error.message}</div>;
}
```

## Best Practices

1. **Use Query Keys Consistently**: Always use the `productKeys` object
2. **Enable/Disable Queries**: Use `enabled` option to control query execution
3. **Optimistic Updates**: Use for better UX on mutations
4. **Prefetch on Hover**: Improve perceived performance
5. **Handle Loading States**: Always show loading indicators
6. **Error Boundaries**: Wrap components with error boundaries
7. **Pagination**: Use cursor-based pagination for large lists
8. **Stale Time**: Adjust based on data volatility

## Testing

### Mock React Query in Tests
```typescript
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const createTestQueryClient = () => new QueryClient({
  defaultOptions: {
    queries: { retry: false },
    mutations: { retry: false },
  },
});

// In test
const queryClient = createTestQueryClient();
render(
  <QueryClientProvider client={queryClient}>
    <YourComponent />
  </QueryClientProvider>
);
```

## Performance Tips

1. **Use `staleTime`**: Reduce unnecessary refetches
2. **Use `gcTime`**: Control cache retention
3. **Paginate Large Lists**: Don't fetch all data at once
4. **Prefetch Critical Data**: Improve perceived load time
5. **Debounce Search**: Delay API calls for search inputs
6. **Infinite Queries**: For infinite scroll use `useInfiniteQuery`

## Troubleshooting

### Queries Not Refetching
- Check `staleTime` configuration
- Verify query key changes trigger refetch
- Ensure `enabled` option is true

### Cache Not Updating
- Verify mutation invalidates correct queries
- Check query key structure
- Use React Query Devtools to inspect cache

### Memory Leaks
- Ensure components properly unmount
- Check `gcTime` is not too long
- Remove unused queries from cache

## Additional Resources

- [React Query Docs](https://tanstack.com/query/latest)
- [Best Practices Guide](https://tkdodo.eu/blog/practical-react-query)
- [API Integration Guide](https://tanstack.com/query/latest/docs/react/guides/queries)
