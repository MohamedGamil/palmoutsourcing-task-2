/**
 * Example: Product Management Component
 * Demonstrates usage of all React Query hooks
 */

'use client';

import { useState } from 'react';
import {
  useProducts,
  useProduct,
  useCreateProduct,
  useUpdateProduct,
  useDeleteProduct,
  useProductStats,
  useScrapeProducts,
} from '@/hooks/use-products';
import type { Platform } from '@/types/api';

export default function ProductManagementExample() {
  const [page, setPage] = useState(1);
  const [platform, setPlatform] = useState<Platform | ''>('');
  const [selectedProductId, setSelectedProductId] = useState<number | null>(null);
  const [newProductUrl, setNewProductUrl] = useState('');

  // Fetch products list with filters
  const {
    data: productsData,
    isLoading: isLoadingProducts,
    error: productsError,
    refetch: refetchProducts,
  } = useProducts({
    page,
    per_page: 10,
    ...(platform && { platform: platform as Platform }),
  });

  // Fetch single product
  const { data: selectedProduct, isLoading: isLoadingProduct } = useProduct(
    selectedProductId || 0,
    {
      enabled: !!selectedProductId, // Only fetch if ID is set
    }
  );

  // Fetch stats
  const { data: stats } = useProductStats();

  // Create product mutation
  const createProduct = useCreateProduct({
    onSuccess: () => {
      setNewProductUrl('');
      alert('Product created successfully!');
    },
    onError: (error) => {
      alert(`Failed to create product: ${error.message}`);
    },
  });

  // Update product mutation
  const updateProduct = useUpdateProduct({
    onSuccess: () => {
      alert('Product updated successfully!');
    },
  });

  // Delete product mutation
  const deleteProduct = useDeleteProduct({
    onSuccess: () => {
      setSelectedProductId(null);
      alert('Product deleted successfully!');
    },
  });

  // Scrape products mutation
  const scrapeProducts = useScrapeProducts({
    onSuccess: (response) => {
      alert(`Scraping complete: ${response.results.successful} successful, ${response.results.failed} failed`);
    },
  });

  // Handlers
  const handleCreateProduct = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newProductUrl) return;
    createProduct.mutate({ product_url: newProductUrl });
  };

  const handleToggleActive = (id: number, currentStatus: boolean) => {
    updateProduct.mutate({
      id,
      data: { is_active: !currentStatus },
    });
  };

  const handleDeleteProduct = (id: number) => {
    if (confirm('Are you sure you want to delete this product?')) {
      deleteProduct.mutate(id);
    }
  };

  const handleScrapeProduct = (id: number) => {
    scrapeProducts.mutate({ product_ids: [id] });
  };

  return (
    <div className="container mx-auto p-6 space-y-8">
      {/* Stats Dashboard */}
      <section>
        <h2 className="text-2xl font-bold mb-4">Statistics</h2>
        {stats && (
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="bg-blue-100 p-4 rounded-lg">
              <p className="text-sm text-gray-600">Total Products</p>
              <p className="text-3xl font-bold">{stats.total_products}</p>
            </div>
            <div className="bg-green-100 p-4 rounded-lg">
              <p className="text-sm text-gray-600">Active</p>
              <p className="text-3xl font-bold">{stats.active_products}</p>
            </div>
            <div className="bg-yellow-100 p-4 rounded-lg">
              <p className="text-sm text-gray-600">Amazon</p>
              <p className="text-3xl font-bold">{stats.platforms.amazon}</p>
            </div>
            <div className="bg-purple-100 p-4 rounded-lg">
              <p className="text-sm text-gray-600">Jumia</p>
              <p className="text-3xl font-bold">{stats.platforms.jumia}</p>
            </div>
          </div>
        )}
      </section>

      {/* Create Product Form */}
      <section>
        <h2 className="text-2xl font-bold mb-4">Watch New Product</h2>
        <form onSubmit={handleCreateProduct} className="flex gap-2">
          <input
            type="url"
            value={newProductUrl}
            onChange={(e) => setNewProductUrl(e.target.value)}
            placeholder="Enter product URL (Amazon or Jumia)"
            className="flex-1 px-4 py-2 border rounded-lg"
            required
          />
          <button
            type="submit"
            disabled={createProduct.isPending}
            className="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:bg-gray-400"
          >
            {createProduct.isPending ? 'Creating...' : 'Watch Product'}
          </button>
        </form>
        {createProduct.isError && (
          <p className="text-red-500 mt-2">{createProduct.error.message}</p>
        )}
      </section>

      {/* Filters */}
      <section>
        <h2 className="text-2xl font-bold mb-4">Products</h2>
        <div className="flex gap-4 mb-4">
          <select
            value={platform}
            onChange={(e) => {
              setPlatform(e.target.value as Platform | '');
              setPage(1);
            }}
            className="px-4 py-2 border rounded-lg"
          >
            <option value="">All Platforms</option>
            <option value="amazon">Amazon</option>
            <option value="jumia">Jumia</option>
          </select>

          <button
            onClick={() => refetchProducts()}
            className="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300"
          >
            Refresh
          </button>
        </div>

        {/* Products List */}
        {isLoadingProducts ? (
          <div className="text-center py-8">Loading products...</div>
        ) : productsError ? (
          <div className="text-red-500 py-8">Error: {productsError.message}</div>
        ) : (
          <>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {productsData?.data.map((product) => (
                <div
                  key={product.id}
                  className="border rounded-lg p-4 hover:shadow-lg transition-shadow"
                >
                  {product.image_url && (
                    <img
                      src={product.image_url}
                      alt={product.title}
                      className="w-full h-48 object-cover rounded mb-3"
                    />
                  )}
                  <h3 className="font-semibold text-lg mb-2 line-clamp-2">
                    {product.title}
                  </h3>
                  <div className="space-y-1 text-sm mb-3">
                    <p className="text-xl font-bold text-green-600">
                      {product.price_currency} {product.price}
                    </p>
                    {product.rating && (
                      <p>⭐ {product.rating} ({product.rating_count} reviews)</p>
                    )}
                    <p className="text-gray-600">
                      Platform: <span className="font-medium">{product.platform}</span>
                    </p>
                    <p className="text-gray-600">
                      Status:{' '}
                      <span className={product.is_active ? 'text-green-600' : 'text-red-600'}>
                        {product.is_active ? 'Active' : 'Inactive'}
                      </span>
                    </p>
                  </div>

                  {/* Actions */}
                  <div className="flex flex-wrap gap-2">
                    <button
                      onClick={() => setSelectedProductId(product.id)}
                      className="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600"
                    >
                      View Details
                    </button>
                    <button
                      onClick={() => handleToggleActive(product.id, product.is_active)}
                      disabled={updateProduct.isPending}
                      className="px-3 py-1 bg-yellow-500 text-white text-sm rounded hover:bg-yellow-600"
                    >
                      {product.is_active ? 'Deactivate' : 'Activate'}
                    </button>
                    <button
                      onClick={() => handleScrapeProduct(product.id)}
                      disabled={scrapeProducts.isPending}
                      className="px-3 py-1 bg-green-500 text-white text-sm rounded hover:bg-green-600"
                    >
                      Scrape
                    </button>
                    <button
                      onClick={() => handleDeleteProduct(product.id)}
                      disabled={deleteProduct.isPending}
                      className="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600"
                    >
                      Delete
                    </button>
                  </div>
                </div>
              ))}
            </div>

            {/* Pagination */}
            {productsData && productsData.meta.last_page > 1 && (
              <div className="flex justify-center items-center gap-4 mt-6">
                <button
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                  disabled={page === 1}
                  className="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 disabled:opacity-50"
                >
                  Previous
                </button>
                <span className="text-gray-600">
                  Page {page} of {productsData.meta.last_page}
                </span>
                <button
                  onClick={() => setPage((p) => p + 1)}
                  disabled={page === productsData.meta.last_page}
                  className="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 disabled:opacity-50"
                >
                  Next
                </button>
              </div>
            )}
          </>
        )}
      </section>

      {/* Selected Product Details Modal */}
      {selectedProductId && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="flex justify-between items-start mb-4">
              <h2 className="text-2xl font-bold">Product Details</h2>
              <button
                onClick={() => setSelectedProductId(null)}
                className="text-gray-500 hover:text-gray-700 text-2xl"
              >
                ×
              </button>
            </div>

            {isLoadingProduct ? (
              <div className="text-center py-8">Loading...</div>
            ) : selectedProduct ? (
              <div className="space-y-4">
                {selectedProduct.image_url && (
                  <img
                    src={selectedProduct.image_url}
                    alt={selectedProduct.title}
                    className="w-full max-h-64 object-contain rounded"
                  />
                )}
                <div>
                  <h3 className="font-semibold text-lg">{selectedProduct.title}</h3>
                  <p className="text-2xl font-bold text-green-600 mt-2">
                    {selectedProduct.price_currency} {selectedProduct.price}
                  </p>
                </div>
                <div className="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <p className="text-gray-600">Platform</p>
                    <p className="font-medium">{selectedProduct.platform}</p>
                  </div>
                  <div>
                    <p className="text-gray-600">Status</p>
                    <p className={selectedProduct.is_active ? 'text-green-600' : 'text-red-600'}>
                      {selectedProduct.is_active ? 'Active' : 'Inactive'}
                    </p>
                  </div>
                  <div>
                    <p className="text-gray-600">Category</p>
                    <p className="font-medium">{selectedProduct.platform_category || 'N/A'}</p>
                  </div>
                  <div>
                    <p className="text-gray-600">Scrape Count</p>
                    <p className="font-medium">{selectedProduct.scrape_count}</p>
                  </div>
                </div>
                <div>
                  <p className="text-gray-600 text-sm">Product URL</p>
                  <a
                    href={selectedProduct.product_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-blue-500 hover:underline text-sm break-all"
                  >
                    {selectedProduct.product_url}
                  </a>
                </div>
              </div>
            ) : (
              <div className="text-center py-8">Product not found</div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
