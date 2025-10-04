'use client';

import { useState } from 'react';
import PageTitle from '@/components/PageTitle';
import { useProductStats } from '@/lib/product-api';

export default function StatsPage() {
  const [autoRefresh, setAutoRefresh] = useState(false);
  
  const { data: stats, isLoading, error, refetch } = useProductStats({
    refetchInterval: autoRefresh ? 30000 : false, // Refresh every 30 seconds if enabled
  });

  const handleRefresh = () => {
    refetch();
  };

  const toggleAutoRefresh = () => {
    setAutoRefresh(!autoRefresh);
  };

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="animate-pulse">
            <PageTitle
              title="Statistics"
              subtitle="Product tracking and scraping statistics"
            />
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              {[...Array(8)].map((_, i) => (
                <div key={i} className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                  <div className="h-4 bg-gray-200 dark:bg-gray-600 rounded mb-2 w-1/2"></div>
                  <div className="h-8 bg-gray-200 dark:bg-gray-600 rounded w-3/4"></div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <PageTitle
            title="Statistics"
            subtitle="Product tracking and scraping statistics"
          />
          <div className="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-md p-4">
            <div className="flex">
              <div className="ml-3">
                <h3 className="text-sm font-medium text-red-800 dark:text-red-200">
                  Error loading statistics
                </h3>
                <div className="mt-2 text-sm text-red-700 dark:text-red-300">
                  {error instanceof Error ? error.message : 'An error occurred while loading stats'}
                </div>
                <div className="mt-4">
                  <button
                    onClick={handleRefresh}
                    className="bg-red-100 dark:bg-red-800 hover:bg-red-200 dark:hover:bg-red-700 text-red-800 dark:text-red-200 px-3 py-2 rounded-md text-sm font-bold"
                  >
                    Try again
                  </button>
                </div>
              </div>
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
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
          <PageTitle
            title="Statistics"
            subtitle="Product tracking and scraping statistics"
          />
          
          <div className="flex items-center gap-3 mt-4 sm:mt-0">
            <button
              onClick={toggleAutoRefresh}
              className={`px-4 py-2 text-sm font-medium rounded-md transition-colors cursor-pointer ${
                autoRefresh
                  ? 'bg-green-600 text-white hover:bg-green-700'
                  : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
              }`}
            >
              {autoRefresh ? (
                <>
                  <span className="inline-block animate-pulse mr-2">●</span>
                  Auto-refresh ON
                </>
              ) : (
                'Auto-refresh OFF'
              )}
            </button>
            
            <button
              onClick={handleRefresh}
              disabled={isLoading}
              className="cursor-pointer inline-flex items-center px-4 py-2 border border-transparent text-sm font-bold rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isLoading ? (
                <>
                  <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Refreshing...
                </>
              ) : (
                <>
                  Refresh Stats
                </>
              )}
            </button>
          </div>
        </div>

        {stats && (
          <>
            {/* Overview Stats */}
            <div className="mb-8">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Overview
              </h2>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {/* Total Products */}
                <StatCard
                  title="Total Products"
                  value={stats.total_products}
                  icon="📦"
                  color="blue"
                />

                {/* Active Products */}
                <StatCard
                  title="Active Products"
                  value={stats.active_products}
                  icon="✅"
                  color="green"
                  subtitle={`${stats.inactive_products} inactive`}
                />

                {/* Amazon Products */}
                <StatCard
                  title="Amazon Products"
                  value={stats.by_platform.amazon}
                  icon="📦"
                  color="orange"
                />

                {/* Jumia Products */}
                <StatCard
                  title="Jumia Products"
                  value={stats.by_platform.jumia}
                  icon="🛍️"
                  color="purple"
                />
              </div>
            </div>

            {/* Scraping Stats */}
            <div className="mb-8">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Scraping Activity
              </h2>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {/* Products Scraped Today */}
                <StatCard
                  title="Scraped Today"
                  value={stats.scraping_stats.products_scraped_today}
                  icon="🔄"
                  color="blue"
                />

                {/* Total Scrapes */}
                <StatCard
                  title="Total Scrapes"
                  value={stats.scraping_stats.total_scrapes}
                  icon="📅"
                  color="indigo"
                />

                {/* Never Scraped */}
                <StatCard
                  title="Never Scraped"
                  value={stats.scraping_stats.products_never_scraped}
                  icon="⏳"
                  color="yellow"
                  alert={stats.scraping_stats.products_never_scraped > 0}
                />

                {/* Avg Scrapes Per Product */}
                <StatCard
                  title="Avg Scrapes/Product"
                  value={stats.scraping_stats.avg_scrapes_per_product.toFixed(1)}
                  icon="📊"
                  color="purple"
                />
              </div>
            </div>

            {/* Price & Rating Stats */}
            <div className="mb-8">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Product Insights
              </h2>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {/* Average Price */}
                <StatCard
                  title="Average Price"
                  value={stats.price_stats.avg > 0 ? `$${stats.price_stats.avg.toFixed(2)}` : 'N/A'}
                  icon="💰"
                  color="green"
                />

                {/* Highest Price */}
                <StatCard
                  title="Highest Price"
                  value={stats.price_stats.max > 0 ? `$${stats.price_stats.max.toFixed(2)}` : 'N/A'}
                  icon="📈"
                  color="blue"
                />

                {/* Lowest Price */}
                <StatCard
                  title="Lowest Price"
                  value={stats.price_stats.min > 0 ? `$${stats.price_stats.min.toFixed(2)}` : 'N/A'}
                  icon="📉"
                  color="orange"
                />

                {/* Average Rating */}
                <StatCard
                  title="Average Rating"
                  value={stats.rating_stats.avg > 0 ? `⭐ ${stats.rating_stats.avg.toFixed(1)}` : 'N/A'}
                  icon="⭐"
                  color="yellow"
                />
              </div>
            </div>

            {/* Platform Comparison */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Amazon Stats */}
              <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                  <span className="text-2xl mr-2">📦</span>
                  Amazon Statistics
                </h3>
                <div className="space-y-3">
                  <StatRow
                    label="Total Products"
                    value={stats.by_platform.amazon}
                  />
                  <StatRow
                    label="Platform Share"
                    value={stats.total_products > 0 ? `${((stats.by_platform.amazon / stats.total_products) * 100).toFixed(1)}%` : 'N/A'}
                  />
                </div>
              </div>

              {/* Jumia Stats */}
              <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                  <span className="text-2xl mr-2">🛍️</span>
                  Jumia Statistics
                </h3>
                <div className="space-y-3">
                  <StatRow
                    label="Total Products"
                    value={stats.by_platform.jumia}
                  />
                  <StatRow
                    label="Platform Share"
                    value={stats.total_products > 0 ? `${((stats.by_platform.jumia / stats.total_products) * 100).toFixed(1)}%` : 'N/A'}
                  />
                </div>
              </div>
            </div>

            {/* Last Updated */}
            <div className="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
              Last updated: {new Date().toLocaleString()}
              {autoRefresh && ' (Auto-refreshing every 30 seconds)'}
            </div>
          </>
        )}
      </div>
    </div>
  );
}

// StatCard Component
interface StatCardProps {
  title: string;
  value: string | number;
  icon: string;
  color: 'blue' | 'green' | 'orange' | 'purple' | 'yellow' | 'red' | 'indigo';
  subtitle?: string;
  alert?: boolean;
}

function StatCard({ title, value, icon, color, subtitle, alert }: StatCardProps) {
  const colorClasses = {
    blue: 'bg-blue-50 dark:bg-blue-900 border-blue-200 dark:border-blue-700',
    green: 'bg-green-50 dark:bg-green-900 border-green-200 dark:border-green-700',
    orange: 'bg-orange-50 dark:bg-orange-900 border-orange-200 dark:border-orange-700',
    purple: 'bg-purple-50 dark:bg-purple-900 border-purple-200 dark:border-purple-700',
    yellow: 'bg-yellow-50 dark:bg-yellow-900 border-yellow-200 dark:border-yellow-700',
    red: 'bg-red-50 dark:bg-red-900 border-red-200 dark:border-red-700',
    indigo: 'bg-indigo-50 dark:bg-indigo-900 border-indigo-200 dark:border-indigo-700',
  };

  const textColorClasses = {
    blue: 'text-blue-900 dark:text-blue-100',
    green: 'text-green-900 dark:text-green-100',
    orange: 'text-orange-900 dark:text-orange-100',
    purple: 'text-purple-900 dark:text-purple-100',
    yellow: 'text-yellow-900 dark:text-yellow-100',
    red: 'text-red-900 dark:text-red-100',
    indigo: 'text-indigo-900 dark:text-indigo-100',
  };

  return (
    <div className={`${colorClasses[color]} border rounded-lg shadow p-6 ${alert ? 'ring-2 ring-red-500 animate-pulse' : ''}`}>
      <div className="flex items-center justify-between mb-2">
        <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">
          {title}
        </h3>
        <span className="text-2xl">{icon}</span>
      </div>
      <p className={`text-3xl font-bold ${textColorClasses[color]}`}>
        {value}
      </p>
      {subtitle && (
        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
          {subtitle}
        </p>
      )}
    </div>
  );
}

// StatRow Component
interface StatRowProps {
  label: string;
  value: string | number;
}

function StatRow({ label, value }: StatRowProps) {
  return (
    <div className="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700 last:border-0">
      <span className="text-sm text-gray-600 dark:text-gray-400">{label}</span>
      <span className="text-sm font-semibold text-gray-900 dark:text-white">{value}</span>
    </div>
  );
}
