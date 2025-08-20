<?php

namespace App\Console\Commands;

use App\Services\ShopifyImportService;
use Illuminate\Console\Command;

class ImportShopifyProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:import-products {shop : Shop domain (e.g., myshop.myshopify.com)} {--clear : Clear existing products before import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tất cả products từ Shopify về database';

    protected ShopifyImportService $importService;

    public function __construct(ShopifyImportService $importService)
    {
        parent::__construct();
        $this->importService = $importService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shop = $this->argument('shop');
        $shouldClear = $this->option('clear');

        $this->info("Bắt đầu import products từ shop: {$shop}");

        // Clear existing data if requested
        if ($shouldClear) {
            $this->warn('Đang xóa dữ liệu products hiện có...');
            if ($this->importService->clearShopData($shop)) {
                $this->info('✓ Đã xóa dữ liệu products cũ');
            } else {
                $this->error('✗ Không thể xóa dữ liệu products cũ');
                return 1;
            }
        }

        // Import products
        $this->info('Đang import products...');
        $stats = $this->importService->importProducts($shop);

        // Display results
        $this->line('');
        $this->info('=== KẾT QUẢ IMPORT PRODUCTS ===');
        $this->info("Total products imported: {$stats['total_products']}");
        $this->info("Total variants imported: {$stats['total_variants']}");
        $this->info("Total images imported: {$stats['total_images']}");

        if (!empty($stats['errors'])) {
            $this->line('');
            $this->error('Có lỗi xảy ra:');
            foreach ($stats['errors'] as $error) {
                $this->error("- {$error}");
            }
            return 1;
        }

        $this->line('');
        $this->info('✓ Import products hoàn thành thành công!');
        return 0;
    }
}
