'use client';

import PageTitle from '@/components/PageTitle';
import Modal from '@/components/Modal';
import AddProductForm from '@/components/AddProductForm';
import { useState, useEffect, useRef } from 'react';
import { useProducts, useDeleteProduct, useUpdateProduct, Product, type Platform } from '@/lib/product-api';
import Image from 'next/image';

type PlatformFilter = 'all' | Platform;

export default function ProductsPage() {
  const [platformFilter, setPlatformFilter] = useState<PlatformFilter>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [searchInput, setSearchInput] = useState(''); // Input field state
  const [currentPage, setCurrentPage] = useState(1);
  const [perPage, setPerPage] = useState(12);
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Fetch products with React Query
  const { data, isLoading, refetch } = useProducts({
    page: currentPage,
    per_page: perPage,
    platform: platformFilter === 'all' ? undefined : platformFilter,
    search: searchQuery || undefined,
  });

  const products = data?.data || [];
  const pagination = data?.meta;
  const debounceTimerRef = useRef<NodeJS.Timeout | null>(null);

  // Mutations
  const deleteProduct = useDeleteProduct({
    onError: (err) => {
      setError(err instanceof Error ? err.message : 'Failed to delete product');
    },
  });

  const updateProduct = useUpdateProduct({
    onError: (err) => {
      setError(err instanceof Error ? err.message : 'Failed to update product');
    },
  });

  const updateSearchInput = (value: string) => {
    setSearchInput(value);

    // Clear existing timer
    if (debounceTimerRef.current) {
      clearTimeout(debounceTimerRef.current);
    }
    
    // Set new timer to update search query after 500ms of no typing
    debounceTimerRef.current = setTimeout(() => {
      setSearchQuery(value);
      setCurrentPage(1);
    }, 500);
  };

  const handleFilterChange = (newFilter: PlatformFilter) => {
    setPlatformFilter(newFilter);
    setCurrentPage(1); // Reset to first page when filter changes
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleRefresh = () => {
    refetch();
  };

  const handlePerPageChange = (newPerPage: number) => {
    setPerPage(newPerPage);
    setCurrentPage(1); // Reset to first page when changing items per page
  };

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setSearchQuery(searchInput);
    setCurrentPage(1); // Reset to first page when searching
  };

  const handleClearSearch = () => {
    setSearchInput('');
    setSearchQuery('');
    setCurrentPage(1);
  };

  const handleProductCreated = (product: Product) => {
    setIsAddModalOpen(false);
    handleClearSearch();
    handleFilterChange('all');
    refetch();
  };

  const handleProductError = (errorMessage: string) => {
    setError(errorMessage);
  };

  const handleDeleteProduct = async (productId: number) => {
    if (!confirm('Are you sure you want to delete this product?')) {
      return;
    }

    setError(null);
    deleteProduct.mutate(productId);
  };

  const handleToggleActive = (product: Product) => {
    setError(null);
    updateProduct.mutate({
      id: product.id,
      data: { is_active: !product.is_active },
    });
  };

  const handleOpenAddModal = () => {
    setError(null);
    setIsAddModalOpen(true);
  };

  const handleCloseAddModal = () => {
    setIsAddModalOpen(false);
  };

  const handleClearErrors = () => {
    setError(null);
  };

  const getPlatformColor = (platform: Platform) => {
    switch (platform) {
      case 'amazon':
        return 'bg-orange-100 text-orange-800 dark:bg-amber-900 dark:text-white';
      case 'jumia':
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-white';
      default:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-white';
    }
  };

  const getPlatformIcon = (platform: Platform) => {
    switch (platform) {
      case 'amazon':
        return '📦';
      case 'jumia':
        return '🛍️';
      default:
        return '🏪';
    }
  };

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      if (debounceTimerRef.current) {
        clearTimeout(debounceTimerRef.current);
      }
    };
  }, []);

  const PageTitleSection = (
    <PageTitle
      title="Products"
      subtitle="Track and manage product prices across Amazon and Jumia."
      button
      buttonText="Add Product"
      buttonHandler={handleOpenAddModal}
    />
  );

  const PaginationSection = pagination && pagination.last_page > 1 ? (
    <div className="mt-8 flex items-center justify-between">
      <div className="flex-1 flex justify-between sm:hidden">
        <button
          onClick={() => handlePageChange(currentPage - 1)}
          disabled={currentPage === 1}
          className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
        >
          Previous
        </button>
        <button
          onClick={() => handlePageChange(currentPage + 1)}
          disabled={currentPage === pagination.last_page}
          className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
        >
          Next
        </button>
      </div>
      <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p className="text-sm text-gray-700 dark:text-gray-300">
            Page <span className="font-medium">{pagination.current_page}</span> of{' '}
            <span className="font-medium">{pagination.last_page}</span>
            {' '} - {' '}
            (<span className="font-medium">{pagination.total}</span> total results)
          </p>
        </div>
        <div>
          <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <button
              onClick={() => handlePageChange(currentPage - 1)}
              disabled={currentPage === 1}
              className="cursor-pointer relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
            >
              <span className="sr-only">Previous</span>
              <svg className="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fillRule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clipRule="evenodd" />
              </svg>
            </button>

            {/* Page Numbers */}
            {Array.from({ length: Math.min(5, pagination.last_page) }, (_, i) => {
              let pageNumber;
              if (pagination.last_page <= 5) {
                pageNumber = i + 1;
              } else if (currentPage <= 3) {
                pageNumber = i + 1;
              } else if (currentPage >= pagination.last_page - 2) {
                pageNumber = pagination.last_page - 4 + i;
              } else {
                pageNumber = currentPage - 2 + i;
              }

              return (
                <button
                  key={pageNumber}
                  onClick={() => handlePageChange(pageNumber)}
                  className={`cursor-pointer relative inline-flex items-center px-4 py-2 border text-sm font-medium ${pageNumber === currentPage
                      ? 'z-10 bg-blue-50 border-blue-500 text-blue-600 dark:bg-blue-900 dark:border-blue-600 dark:text-blue-200'
                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700'
                    }`}
                >
                  {pageNumber}
                </button>
              );
            })}

            <button
              onClick={() => handlePageChange(currentPage + 1)}
              disabled={currentPage === pagination.last_page}
              className="cursor-pointer relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
            >
              <span className="sr-only">Next</span>
              <svg className="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clipRule="evenodd" />
              </svg>
            </button>
          </nav>
        </div>
      </div>
    </div>
  ) : (<></>);

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="animate-pulse">
            <PageTitle
              title="Products"
              subtitle="Track and manage product prices across Amazon and Jumia."
              button
              buttonText="Add Product"
              buttonHandler={handleOpenAddModal}
            />
            <div className="space-y-4">
              {[...Array(5)].map((_, i) => (
                <div key={i} className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                  <div className="h-4 bg-gray-200 dark:bg-gray-600 rounded mb-2 w-3/4"></div>
                  <div className="h-3 bg-gray-200 dark:bg-gray-600 rounded mb-2 w-1/2"></div>
                  <div className="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/4"></div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        {PageTitleSection}

        {/* Search Bar */}
        <div className="mb-6">
        </div>

        {/* Filter Buttons and Per Page Selector */}
        <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div className="flex flex-wrap gap-4">
            <div className="flex flex-wrap gap-2">
              {(['all', 'amazon', 'jumia'] as const).map((platform) => (
                <button
                  key={platform}
                  onClick={() => handleFilterChange(platform)}
                  className={`px-4 py-2 rounded-lg text-sm font-bold transition-colors cursor-pointer ${platformFilter === platform
                      ? 'bg-blue-600 text-white'
                      : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                    }`}
                >
                  {platform === 'all' ? 'All Products' :
                    platform.charAt(0).toUpperCase() + platform.slice(1)}
                </button>
              ))}
            </div>
            <div className="flex flex-wrap gap-2">
              <form onSubmit={handleSearchSubmit} className="flex gap-2">
                <div className="flex-1 relative">
                  <input
                    type="text"
                    value={searchInput}
                    onChange={(e) => updateSearchInput(e.target.value)}
                    placeholder="Search products by title..."
                    className="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400"
                  />
                  <svg
                    className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path
                      fillRule="evenodd"
                      d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                      clipRule="evenodd"
                    />
                  </svg>
                </div>
                {/* <button
                  type="submit"
                  className="cursor-pointer px-6 py-2 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  disabled={isLoading}
                >
                  Search
                </button> */}
                {searchQuery && (
                  <button
                    type="button"
                    onClick={handleClearSearch}
                    className="cursor-pointer px-4 py-2 bg-gray-200 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                  >
                    Clear
                  </button>
                )}
              </form>
              {/* {searchQuery && (
                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                  Searching for: <span className="font-semibold">&quot;{searchQuery}&quot;</span>
                </p>
              )} */}
            </div>
          </div>

          <div className="flex items-end space-x-4">
            <div className="flex items-center space-x-2">
              <label htmlFor="per-page" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                Show:
              </label>
              <select
                id="per-page"
                value={perPage}
                onChange={(e) => handlePerPageChange(Number(e.target.value))}
                className="block px-3 py-2 border border-gray-300 rounded-md text-sm font-medium bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-blue-400"
              >
                <option value={12}>12 per page</option>
                <option value={24}>24 per page</option>
                <option value={48}>48 per page</option>
              </select>
            </div>
            <button
              onClick={handleRefresh}
              disabled={isLoading}
              className="cursor-pointer inline-flex items-center px-4 py-2 border border-transparent text-sm font-bold rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isLoading ? (
                <>
                  <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Refreshing...
                </>
              ) : (
                <>
                  Refresh List
                </>
              )}
            </button>
          </div>
        </div>

        <div className='block pt-2 pb-6'>
          {PaginationSection}
        </div>

        {/* Error State */}
        {error && (
          <div className="mb-6 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-md p-4">
            <div className="flex relative">
              <div className="ml-3">
                <button
                  onClick={handleClearErrors}
                  className="ml-auto flex-shrink-0 absolute top-0 right-0 p-1.5 text-red-400 hover:text-red-600 dark:text-red-300 dark:hover:text-red-100 cursor-pointer"
                  aria-label="Close error message"
                >
                  <svg className="h-5 w-5 text-red-400 hover:text-red-600 dark:text-red-300 dark:hover:text-red-100" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                  </svg>
                </button>
                <h3 className="text-sm font-medium text-red-800 dark:text-red-200">
                  Error loading products
                </h3>
                <div className="mt-2 text-sm text-red-700 dark:text-red-300">
                  {error}
                </div>
                {/* <div className="mt-4">
                  <button
                    onClick={handleRefresh}
                    className="bg-red-100 dark:bg-red-800 hover:bg-red-200 dark:hover:bg-red-700 text-red-800 dark:text-red-200 px-3 py-2 rounded-md text-sm font-bold"
                  >
                    Try again
                  </button>
                </div> */}
              </div>
            </div>
          </div>
        )}

        {/* Products List */}
        {products.length === 0 && !isLoading && !error ? (
          <div className="text-center py-12">
            <div className="text-6xl mb-4"> </div>
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
              {platformFilter === 'all' ? 'No products found' : `No ${platformFilter} products`}
            </h3>
            <p className="text-gray-500 dark:text-gray-400">
              {platformFilter === 'all'
                ? 'Get started by adding your first product to track.'
                : `There are no ${platformFilter} products at the moment.`}
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {products.map((product) => (
              <div key={product.id} className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow">
                {/* Product Image */}
                <div className="relative h-48 min-h-[12rem] max-h-[12rem] w-full bg-gray-100 dark:bg-gray-700">
                  {product.image_url ? (
                    <Image
                      src={product.image_url}
                      alt={product.title}
                      fill
                      className="object-contain p-4"
                      unoptimized
                      onError={(e) => {
                        // Fallback to placeholder on error
                        const target = e.target as HTMLImageElement;
                        target.style.display = 'none';
                        const parent = target.parentElement;
                        if (parent) {
                          parent.innerHTML = `
                            <div class="flex items-center justify-center h-full cursor-default select-none">
                              <div class="text-center">
                                <div class="text-6xl mb-2">📦</div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Image not available</p>
                              </div>
                            </div>
                          `;
                        }
                      }}
                    />
                  ) : (
                    <div className="flex items-center justify-center h-full cursor-default select-none">
                      <div className="text-center">
                        <div className="text-6xl mb-2">📦</div>
                        <p className="text-sm text-gray-500 dark:text-gray-400">Product has no image</p>
                      </div>
                    </div>
                  )}
                </div>

                {/* Product Info */}
                <div className="px-4 py-4">
                  <div className="relative min-h-[190px]">
                    <div className="flex items-start justify-between mb-2">
                      <h3 className="text-md font-semibold text-gray-900 dark:text-white line-clamp-2 flex-1">
                        {product.title}
                      </h3>
                    </div>

                    {/* Platform Badge */}
                    <div className="flex items-center gap-2 mb-2">
                      <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-bold ${getPlatformColor(product.platform)}`}>
                        {getPlatformIcon(product.platform)} {product.platform.toUpperCase()}
                      </span>
                      <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-bold ${product.is_active
                          ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                          : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
                        }`}>
                        {product.is_active ? '● Active' : '○ Inactive'}
                      </span>
                    </div>

                    {/* Price and Rating */}
                    <div className="space-y-1 mb-3">
                      {product.price && (
                        <p className="text-lg font-bold text-blue-600 dark:text-blue-400">
                          {Number(product.price).toFixed(2)} {product.price_currency}
                        </p>
                      )}
                      {product.rating && (
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          ⭐ {Number(product.rating).toFixed(1)} / 5.0
                        </p>
                      )}
                    </div>

                    {/* Timestamps */}
                    <div className="text-xs text-gray-500 dark:text-gray-400 space-y-1 mb-3">
                      <p>Added: {new Date(product.created_at).toLocaleDateString()}</p>
                      {product.last_scraped_at && (
                        <p>Last scraped: {new Date(product.last_scraped_at).toLocaleDateString()}</p>
                      )}
                    </div>
                  </div>

                  {/* Actions */}
                  <div className="flex gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <button
                      onClick={() => handleToggleActive(product)}
                      disabled={updateProduct.isPending}
                      className="cursor-pointer flex-1 text-xs font-medium px-3 py-1.5 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800 disabled:opacity-50"
                    >
                      {product.is_active ? 'Deactivate' : 'Activate'}
                    </button>
                    <button
                      onClick={() => handleDeleteProduct(product.id)}
                      disabled={deleteProduct.isPending}
                      className="cursor-pointer text-xs font-medium px-3 py-1.5 rounded bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800 disabled:opacity-50"
                    >
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Pagination */}
        {PaginationSection}
      </div>

      {/* Add Product Modal */}
      <Modal isOpen={isAddModalOpen} onClose={handleCloseAddModal} title="Add New Product">
        <AddProductForm
          onSubmit={handleProductCreated}
          onCancel={handleCloseAddModal}
          onError={handleProductError}
        />
      </Modal>
    </div>
  );
}
