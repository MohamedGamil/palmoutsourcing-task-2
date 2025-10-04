'use client';

import { useState } from 'react';
import { withCSRF } from './CSRFProvider';
import { useCreateProduct, type Product } from '@/lib/product-api';

interface AddProductFormProps {
  onSubmit: (product: Product) => void; // Called with the created product
  onCancel: () => void;
  onError?: (error: string) => void; // Called when there's an error
}

export function AddProductForm({ onSubmit, onCancel, onError }: AddProductFormProps) {
  const [productUrl, setProductUrl] = useState('');
  const [errors, setErrors] = useState<Record<string, string>>({});
  
  const createProduct = useCreateProduct({
    onSuccess: (product) => {
      onSubmit(product);
      setProductUrl('');
    },
    onError: (error) => {
      const errorMessage = error instanceof Error ? error.message : 'Failed to add product';
      if (onError) {
        onError(errorMessage);
      } else {
        setErrors({ submit: errorMessage });
      }
    },
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    // Basic validation
    const newErrors: Record<string, string> = {};
    
    if (!productUrl.trim()) {
      newErrors.url = 'Product URL is required';
    } else if (!isValidUrl(productUrl)) {
      newErrors.url = 'Please enter a valid URL';
    } else if (!isAmazonOrJumiaUrl(productUrl)) {
      newErrors.url = 'URL must be from Amazon or Jumia';
    }

    setErrors(newErrors);
    
    if (Object.keys(newErrors).length === 0) {
      createProduct.mutate({ product_url: productUrl.trim() });
    }
  };

  const isValidUrl = (url: string): boolean => {
    try {
      new URL(url);
      return true;
    } catch {
      return false;
    }
  };

  const isAmazonOrJumiaUrl = (url: string): boolean => {
    const lowerUrl = url.toLowerCase();
    return lowerUrl.includes('amazon.') || lowerUrl.includes('jumia.');
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setProductUrl(e.target.value);
    
    // Clear error when user starts typing
    if (errors.url) {
      setErrors({});
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {errors.submit && (
        <div className="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-md p-3">
          <div className="text-sm text-red-700 dark:text-red-300">
            {errors.submit}
          </div>
        </div>
      )}

      <div>
        <label htmlFor="productUrl" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Product URL <span className="text-red-500">*</span>
        </label>
        <input
          type="url"
          id="productUrl"
          value={productUrl}
          onChange={handleChange}
          placeholder="Enter Amazon or Jumia product URL..."
          className={`mt-1 block w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white ${
            errors.url 
              ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
              : 'border-gray-300 dark:border-gray-600'
          }`}
          disabled={createProduct.isPending}
        />
        {errors.url && (
          <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.url}</p>
        )}
        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
          Supported platforms: Amazon, Jumia
        </p>
      </div>

      <div className="flex justify-end space-x-3 pt-4">
        <button
          type="button"
          onClick={onCancel}
          disabled={createProduct.isPending}
          className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
        >
          Cancel
        </button>
        <button
          type="submit"
          disabled={createProduct.isPending}
          className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {createProduct.isPending ? (
            <>
              <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Adding Product...
            </>
          ) : (
            'Add Product'
          )}
        </button>
      </div>
    </form>
  );
}

export default function AddProductFormWithCSRF(props: AddProductFormProps) {
  return withCSRF(AddProductForm)(props);
}
