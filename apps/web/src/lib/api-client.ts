/**
 * API Client with CSRF token support
 */

import { API_BASE_URL } from "@/constants";


// CSRF token management
class CSRFManager {
  private static csrfToken: string | null = null;
  private static csrfCookie: boolean = false;

  /**
   * Get CSRF cookie from Laravel Sanctum
   */
  static async initCSRF(): Promise<void> {
    if (this.csrfCookie) return;

    try {
      const response = await fetch(`${API_BASE_URL}/sanctum/csrf-cookie`, {
        method: 'GET',
        credentials: 'include', // Important: include cookies
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });

      if (response.ok) {
        this.csrfCookie = true;
        // Extract CSRF token from cookies
        this.csrfToken = this.getCSRFTokenFromCookies();
      }
    } catch (error) {
      console.error('Failed to initialize CSRF:', error);
    }
  }

  /**
   * Get CSRF token from cookies
   */
  private static getCSRFTokenFromCookies(): string | null {
    if (typeof document === 'undefined') return null;

    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
      const [name, value] = cookie.trim().split('=');
      if (name === 'XSRF-TOKEN') {
        return decodeURIComponent(value);
      }
    }
    return null;
  }

  /**
   * Get current CSRF token
   */
  static getToken(): string | null {
    return this.csrfToken || this.getCSRFTokenFromCookies();
  }

  /**
   * Reset CSRF state (useful for testing or logout)
   */
  static reset(): void {
    this.csrfToken = null;
    this.csrfCookie = false;
  }
}

// API Client configuration
interface APIClientOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  body?: any;
  headers?: Record<string, string>;
  requireAuth?: boolean;
  skipCSRF?: boolean;
}

// Standard API response format
// eslint-disable-next-line @typescript-eslint/no-explicit-any
interface APIResponse<T = any> {
  success: boolean;
  message: string;
  data: T;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  errors: Record<string, any>;
}

// API Error class
export class APIError extends Error {
  public statusCode: number;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  public errors: Record<string, any>;

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  constructor(message: string, statusCode: number, errors: Record<string, any> = {}) {
    super(message);
    this.name = 'APIError';
    this.statusCode = statusCode;
    this.errors = errors;
  }
}

/**
 * Enhanced API client with CSRF support
 */
class APIClient {
  private baseURL: string;

  constructor(baseURL: string = API_BASE_URL) {
    this.baseURL = baseURL;
  }

  /**
   * Make an API request with CSRF protection
   */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  async request<T = any>(
    endpoint: string,
    options: APIClientOptions = {}
  ): Promise<APIResponse<T>> {
    const {
      method = 'GET',
      body,
      headers = {},
      requireAuth = false,
      skipCSRF = false,
    } = options;

    // Initialize CSRF for state-changing operations
    if (!skipCSRF && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
      await CSRFManager.initCSRF();
    }

    // Prepare headers
    const requestHeaders: Record<string, string> = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...headers,
    };

    // Add CSRF token for state-changing operations
    if (!skipCSRF && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
      const csrfToken = CSRFManager.getToken();
      if (csrfToken) {
        requestHeaders['X-XSRF-TOKEN'] = csrfToken;
      }
    }

    // Add authorization token if required
    if (requireAuth) {
      const token = this.getAuthToken();
      if (token) {
        requestHeaders['Authorization'] = `Bearer ${token}`;
      }
    }

    // Prepare request config
    const config: RequestInit = {
      method,
      headers: requestHeaders,
      credentials: 'include', // Always include cookies for CSRF
    };

    // Add body for non-GET requests
    if (body && method !== 'GET') {
      config.body = JSON.stringify(body);
    }

    try {
      const response = await fetch(`${this.baseURL}${endpoint}`, config);
      const data = await response.json();

      if (!response.ok) {
        throw new APIError(
          data.message || 'API request failed',
          response.status,
          data.errors || {}
        );
      }

      return data as APIResponse<T>;
    } catch (error) {
      if (error instanceof APIError) {
        throw error;
      }

      // Handle network errors or other issues
      throw new APIError(
        'Network error or server unavailable',
        0,
        { network: (error as Error).message }
      );
    }
  }

  /**
   * GET request
   */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  async get<T = any>(endpoint: string, requireAuth = false): Promise<APIResponse<T>> {
    return this.request<T>(endpoint, { method: 'GET', requireAuth });
  }

  /**
   * POST request
   */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  async post<T = any>(
    endpoint: string,
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    body?: any,
    requireAuth = false
  ): Promise<APIResponse<T>> {
    return this.request<T>(endpoint, { method: 'POST', body, requireAuth });
  }

  /**
   * PUT request
   */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  async put<T = any>(
    endpoint: string,
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    body?: any,
    requireAuth = false
  ): Promise<APIResponse<T>> {
    return this.request<T>(endpoint, { method: 'PUT', body, requireAuth });
  }

  /**
   * PATCH request
   */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  async patch<T = any>(
    endpoint: string,
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    body?: any,
    requireAuth = false
  ): Promise<APIResponse<T>> {
    return this.request<T>(endpoint, { method: 'PATCH', body, requireAuth });
  }

  /**
   * DELETE request
   */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  async delete<T = any>(endpoint: string, requireAuth = false): Promise<APIResponse<T>> {
    return this.request<T>(endpoint, { method: 'DELETE', requireAuth });
  }

  /**
   * Get authentication token (implement based on your auth strategy)
   */
  private getAuthToken(): string | null {
    if (typeof window === 'undefined') return null;
    
    // Try localStorage first
    const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    return token;
  }

  /**
   * Initialize CSRF (can be called manually if needed)
   */
  async initializeCSRF(): Promise<void> {
    await CSRFManager.initCSRF();
  }

  /**
   * Reset CSRF state
   */
  resetCSRF(): void {
    CSRFManager.reset();
  }
}

// Export singleton instance
export const apiClient = new APIClient();

// Export utilities
export { CSRFManager, APIClient };
export type { APIResponse, APIClientOptions };

// Convenience functions
export const api = {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  get: <T = any>(endpoint: string, requireAuth = false) => 
    apiClient.get<T>(endpoint, requireAuth),
  
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  post: <T = any>(endpoint: string, body?: any, requireAuth = false) => 
    apiClient.post<T>(endpoint, body, requireAuth),
  
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  put: <T = any>(endpoint: string, body?: any, requireAuth = false) => 
    apiClient.put<T>(endpoint, body, requireAuth),
  
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  patch: <T = any>(endpoint: string, body?: any, requireAuth = false) => 
    apiClient.patch<T>(endpoint, body, requireAuth),
  
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  delete: <T = any>(endpoint: string, requireAuth = false) => 
    apiClient.delete<T>(endpoint, requireAuth),
  
  initCSRF: () => apiClient.initializeCSRF(),
  resetCSRF: () => apiClient.resetCSRF(),
};
