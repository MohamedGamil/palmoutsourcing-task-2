/**
 * React Query Provider Component
 * Configures and provides React Query client to the application
 */

'use client';

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { useState, type ReactNode } from 'react';

interface ReactQueryProviderProps {
  children: ReactNode;
}

/**
 * Create Query Client with default configuration
 */
const createQueryClient = () => {
  return new QueryClient({
    defaultOptions: {
      queries: {
        // Default options for all queries
        staleTime: 30000, // 30 seconds
        gcTime: 5 * 60 * 1000, // 5 minutes (formerly cacheTime)
        retry: 2, // Retry failed requests twice
        refetchOnWindowFocus: true, // Refetch when window regains focus
        refetchOnReconnect: true, // Refetch when reconnecting
        refetchOnMount: true, // Refetch when component mounts
      },
      mutations: {
        // Default options for all mutations
        retry: 0, // Don't retry mutations by default
        onError: (error) => {
          // Global error handler for mutations
          console.error('Mutation error:', error);
        },
      },
    },
  });
};

/**
 * React Query Provider Component
 * Wraps the application with QueryClientProvider
 */
export default function ReactQueryProvider({ children }: ReactQueryProviderProps) {
  // Create query client instance (only once per component instance)
  const [queryClient] = useState(() => createQueryClient());

  return (
    <QueryClientProvider client={queryClient}>
      {children}
      {/* React Query Devtools - only in development */}
      {process.env.NODE_ENV === 'development' && (
        <ReactQueryDevtools
          initialIsOpen={false}
          position="bottom"
          buttonPosition="bottom-right"
        />
      )}
    </QueryClientProvider>
  );
}
