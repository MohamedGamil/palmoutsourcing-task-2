# React Query Quick Start Guide

## Installation ✅ (Already Done)

```bash
npm install @tanstack/react-query @tanstack/react-query-devtools
```

## Setup ✅ (Already Done)

The React Query provider is already configured in `src/app/layout.tsx`.

## Quick Usage Examples

### 1. Display Product List

```tsx
'use client';

import { useProducts } from '@/lib/product-api';

export default function ProductList() {
  const { data, isLoading, error } = useProducts({ page: 1, per_page: 20 });

  if (isLoading) return <div>Loading...</div>;
  if (error) return <div>Error: {error.message}</div>;

  return (
    <div className="grid gap-4">
      {data?.data.map(product => (
        <div key={product.id} className="p-4 border rounded">
          <h3>{product.title}</h3>
          <p>${product.price}</p>
        </div>
      ))}
    </div>
  );
}
```

### 2. Create New Product

```tsx
'use client';

import { useState } from 'react';
import { useCreateProduct } from '@/lib/product-api';

export default function AddProduct() {
  const [url, setUrl] = useState('');
  const createProduct = useCreateProduct({
    onSuccess: () => alert('Product added!'),
  });

  return (
    <form onSubmit={(e) => {
      e.preventDefault();
      createProduct.mutate({ product_url: url });
    }}>
      <input
        type="url"
        value={url}
        onChange={(e) => setUrl(e.target.value)}
        placeholder="Product URL"
      />
      <button type="submit" disabled={createProduct.isPending}>
        {createProduct.isPending ? 'Adding...' : 'Add Product'}
      </button>
    </form>
  );
}
```

### 3. Show Product Details

```tsx
'use client';

import { useProduct } from '@/lib/product-api';

export default function ProductDetails({ id }: { id: number }) {
  const { data: product, isLoading } = useProduct(id);

  if (isLoading) return <div>Loading...</div>;
  if (!product) return <div>Not found</div>;

  return (
    <div>
      <h1>{product.title}</h1>
      <p>Price: ${product.price} {product.price_currency}</p>
      <p>Platform: {product.platform}</p>
      <p>Rating: ⭐ {product.rating || 'N/A'}</p>
    </div>
  );
}
```

### 4. Update Product

```tsx
'use client';

import { useUpdateProduct } from '@/lib/product-api';

export default function ToggleActive({ id, isActive }: { id: number; isActive: boolean }) {
  const updateProduct = useUpdateProduct();

  return (
    <button
      onClick={() => updateProduct.mutate({
        id,
        data: { is_active: !isActive }
      })}
      disabled={updateProduct.isPending}
    >
      {isActive ? 'Deactivate' : 'Activate'}
    </button>
  );
}
```

### 5. Delete Product

```tsx
'use client';

import { useDeleteProduct } from '@/lib/product-api';

export default function DeleteButton({ id }: { id: number }) {
  const deleteProduct = useDeleteProduct({
    onSuccess: () => alert('Deleted!'),
  });

  return (
    <button
      onClick={() => {
        if (confirm('Delete?')) {
          deleteProduct.mutate(id);
        }
      }}
      disabled={deleteProduct.isPending}
    >
      Delete
    </button>
  );
}
```

### 6. Display Statistics

```tsx
'use client';

import { useProductStats } from '@/lib/product-api';

export default function Stats() {
  const { data: stats } = useProductStats();

  if (!stats) return null;

  return (
    <div className="grid grid-cols-4 gap-4">
      <div>
        <p>Total</p>
        <p className="text-2xl">{stats.total_products}</p>
      </div>
      <div>
        <p>Active</p>
        <p className="text-2xl">{stats.active_products}</p>
      </div>
      <div>
        <p>Amazon</p>
        <p className="text-2xl">{stats.platforms.amazon}</p>
      </div>
      <div>
        <p>Jumia</p>
        <p className="text-2xl">{stats.platforms.jumia}</p>
      </div>
    </div>
  );
}
```

### 7. Filtered Product List

```tsx
'use client';

import { useState } from 'react';
import { useProducts, type Platform } from '@/lib/product-api';

export default function FilteredProducts() {
  const [platform, setPlatform] = useState<Platform | ''>('');
  const [search, setSearch] = useState('');

  const { data, isLoading } = useProducts({
    ...(platform && { platform: platform as Platform }),
    ...(search && { search }),
    per_page: 20,
  });

  return (
    <div>
      <div className="mb-4 flex gap-2">
        <select 
          value={platform} 
          onChange={(e) => setPlatform(e.target.value as Platform | '')}
        >
          <option value="">All</option>
          <option value="amazon">Amazon</option>
          <option value="jumia">Jumia</option>
        </select>
        
        <input
          type="text"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Search..."
        />
      </div>

      {isLoading ? (
        <div>Loading...</div>
      ) : (
        <div className="grid gap-4">
          {data?.data.map(product => (
            <div key={product.id}>{product.title}</div>
          ))}
        </div>
      )}
    </div>
  );
}
```

### 8. Pagination

```tsx
'use client';

import { useState } from 'react';
import { useProducts } from '@/lib/product-api';

export default function PaginatedProducts() {
  const [page, setPage] = useState(1);
  const { data, isLoading } = useProducts({ page, per_page: 10 });

  return (
    <div>
      {isLoading ? (
        <div>Loading...</div>
      ) : (
        <>
          <div className="grid gap-4">
            {data?.data.map(product => (
              <div key={product.id}>{product.title}</div>
            ))}
          </div>

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
        </>
      )}
    </div>
  );
}
```

## Available Imports

```typescript
// Import everything from one place
import {
  // Hooks
  useProducts,
  useProduct,
  useProductStats,
  useCreateProduct,
  useUpdateProduct,
  useDeleteProduct,
  useScrapeProducts,
  
  // Helpers
  usePrefetchProduct,
  useOptimisticProduct,
  productKeys,
  
  // Service
  productService,
  
  // Types
  type Product,
  type Platform,
  type ProductFilters,
  type CreateProductRequest,
  type UpdateProductRequest,
} from '@/lib/product-api';
```

## Common Patterns

### Loading State

```tsx
const { data, isLoading, error } = useProducts();

if (isLoading) return <LoadingSpinner />;
if (error) return <ErrorMessage error={error} />;
if (!data) return <NoData />;

return <DataDisplay data={data} />;
```

### Mutation with Feedback

```tsx
const createProduct = useCreateProduct({
  onSuccess: () => toast.success('Created!'),
  onError: (error) => toast.error(error.message),
});
```

### Optimistic Update

```tsx
const { updateProduct } = useOptimisticProduct();
const mutation = useUpdateProduct();

// Update UI immediately
updateProduct(id, { is_active: false });

// Make API call
mutation.mutate({ id, data: { is_active: false } });
```

### Prefetch on Hover

```tsx
const prefetchProduct = usePrefetchProduct();

<div onMouseEnter={() => prefetchProduct(123)}>
  <Link href={`/products/${123}`}>View Product</Link>
</div>
```

## Documentation

- **Full Reference**: `docs/react-query-integration.md`
- **API Types**: `src/types/api.ts`
- **Hooks**: `src/hooks/use-products.ts`
- **Service**: `src/services/product.service.ts`

## Tips

1. **Always handle loading and error states**
2. **Use TypeScript for type safety**
3. **Leverage automatic cache invalidation**
4. **Use React Query DevTools in development**
5. **Customize staleTime based on data volatility**
6. **Use enabled option for conditional queries**
7. **Implement optimistic updates for better UX**

## Debugging

Open React Query DevTools:
- Look for the floating icon in bottom-right corner (development only)
- Click to open the devtools panel
- Inspect queries, mutations, and cache state
