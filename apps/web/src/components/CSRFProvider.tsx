'use client';

import { useEffect, useState } from 'react';
import { api } from '@/lib/api-client';

interface CSRFProviderProps {
  children: React.ReactNode;
}

/**
 * CSRF Provider component that initializes CSRF token on app start
 */
export default function CSRFProvider({ children }: CSRFProviderProps) {
  const [isInitialized, setIsInitialized] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;

    const initializeCSRF = async () => {
      try {
        await api.initCSRF();
        if (mounted) {
          setIsInitialized(true);
          setError(null);
        }
      } catch (err) {
        if (mounted) {
          setError('Failed to initialize CSRF protection');
          console.error('CSRF initialization error:', err);
          // Still set initialized to true to allow app to load
          setIsInitialized(true);
        }
      }
    };

    initializeCSRF();

    return () => {
      mounted = false;
    };
  }, []);

  // Show loading state while initializing
  if (!isInitialized) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span className="ml-2 text-gray-600">Loading...</span>
      </div>
    );
  }

  // Show error if CSRF failed but still render children
  if (error) {
    console.warn('CSRF Warning:', error);
  }

  return <>{children}</>;
}

/**
 * Hook to manually reinitialize CSRF (useful after logout/login)
 */
export function useCSRF() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const reinitialize = async () => {
    setIsLoading(true);
    setError(null);
    
    try {
      api.resetCSRF();
      await api.initCSRF();
    } catch (err) {
      setError('Failed to reinitialize CSRF protection');
      console.error('CSRF reinitialization error:', err);
    } finally {
      setIsLoading(false);
    }
  };

  return {
    reinitialize,
    isLoading,
    error,
  };
}

/**
 * Higher-order component for CSRF protection
 */
export function withCSRF<P extends object>(
  Component: React.ComponentType<P>
) {
  return function CSRFWrappedComponent(props: P) {
    return (
      <CSRFProvider>
        <Component {...props} />
      </CSRFProvider>
    );
  };
}
