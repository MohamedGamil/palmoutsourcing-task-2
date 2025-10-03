<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Facades\ProxyService;
use Illuminate\Console\Command;

/**
 * Test Proxy Service Command
 * 
 * Console command to test the proxy service integration.
 * This helps verify that the Laravel app can communicate with the Golang proxy service.
 */
class TestProxyServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'scrape:proxy 
                           {--next : Get next proxy only}
                           {--all : Get all proxies}
                           {--status : Get service status}
                           {--health : Check service health}';

    /**
     * The console command description.
     */
    protected $description = 'Test the proxy service integration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing Proxy Service Integration...');
        $this->newLine();

        // Test service health
        if ($this->option('health') || (!$this->option('next') && !$this->option('all') && !$this->option('status'))) {
            $this->testHealth();
        }

        // Test service status
        if ($this->option('status') || (!$this->option('next') && !$this->option('all') && !$this->option('health'))) {
            $this->testStatus();
        }

        // Test getting next proxy
        if ($this->option('next') || (!$this->option('all') && !$this->option('status') && !$this->option('health'))) {
            $this->testNextProxy();
        }

        // Test getting all proxies
        if ($this->option('all') || (!$this->option('next') && !$this->option('status') && !$this->option('health'))) {
            $this->testAllProxies();
        }

        return 0;
    }

    private function testHealth(): void
    {
        $this->info('🔍 Testing proxy service health...');
        
        $startTime = microtime(true);
        $isHealthy = ProxyService::isHealthy();
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        
        if ($isHealthy) {
            $this->info("✅ Service is healthy (response time: {$responseTime}ms)");
        } else {
            $this->error("❌ Service is unhealthy (response time: {$responseTime}ms)");
        }
        
        $this->newLine();
    }

    private function testStatus(): void
    {
        $this->info('📊 Getting proxy service status...');
        
        $startTime = microtime(true);
        $status = ProxyService::getStatus();
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Service Health', $status->isHealthy() ? '✅ Healthy' : '❌ Unhealthy'],
                ['Total Proxies', $status->getTotalProxies()],
                ['Healthy Proxies', $status->getHealthyProxies()],
                ['Unhealthy Proxies', $status->getTotalProxies() - $status->getHealthyProxies()],
                ['Status Message', $status->getMessage()],
                ['Response Time', "{$responseTime}ms"],
            ]
        );
        
        $this->newLine();
    }

    private function testNextProxy(): void
    {
        $this->info('🎯 Getting next proxy...');
        
        $startTime = microtime(true);
        $proxy = ProxyService::getNextProxy();
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        
        if ($proxy) {
            $this->table(
                ['Property', 'Value'],
                [
                    ['Host', $proxy->getHost()],
                    ['Port', $proxy->getPort()],
                    ['URL', $proxy->getUrl()],
                    ['Is Healthy', $proxy->isHealthy() ? '✅ Yes' : '❌ No'],
                    ['Last Checked', $proxy->getLastChecked() ?? 'N/A'],
                    ['Response Time', "{$responseTime}ms"],
                ]
            );
        } else {
            $this->error("❌ No proxy available (response time: {$responseTime}ms)");
        }
        
        $this->newLine();
    }

    private function testAllProxies(): void
    {
        $this->info('📋 Getting all proxies...');
        
        $startTime = microtime(true);
        $proxies = ProxyService::getAllProxies();
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        
        if (empty($proxies)) {
            $this->error("❌ No proxies available (response time: {$responseTime}ms)");
            return;
        }
        
        $this->info("✅ Found " . count($proxies) . " proxies (response time: {$responseTime}ms)");
        
        $tableData = [];
        foreach ($proxies as $index => $proxy) {
            $tableData[] = [
                $index + 1,
                $proxy->getHost(),
                $proxy->getPort(),
                $proxy->getUrl(),
                $proxy->isHealthy() ? '✅' : '❌',
                $proxy->getLastChecked() ?? 'N/A',
            ];
        }
        
        $this->table(
            ['#', 'Host', 'Port', 'URL', 'Healthy', 'Last Checked'],
            $tableData
        );
        
        $this->newLine();
    }
}