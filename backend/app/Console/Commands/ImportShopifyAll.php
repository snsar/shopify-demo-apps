<?php

namespace App\Console\Commands;

use App\Services\ShopifyImportService;
use Illuminate\Console\Command;

class ImportShopifyAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:import-all {shop : Shop domain (e.g., myshop.myshopify.com)} {--clear : Clear existing data before import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tất cả dữ liệu (products và draft orders) từ Shopify về database';

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

        $this->info("Bắt đầu import tất cả dữ liệu từ shop: {$shop}");

        // Clear existing data if requested
        if ($shouldClear) {
            $this->warn('Đang xóa tất cả dữ liệu hiện có...');
            if ($this->importService->clearShopData($shop)) {
                $this->info('✓ Đã xóa tất cả dữ liệu cũ');
            } else {
                $this->error('✗ Không thể xóa dữ liệu cũ');
                return 1;
            }
        }

        // Import all data
        $this->info('Đang import tất cả dữ liệu...');
        $stats = $this->importService->importAll($shop);

        // Display results
        $this->line('');
        $this->info('=== KẾT QUẢ IMPORT TỔNG HỢP ===');

        $this->line('');
        $this->info('PRODUCTS:');
        $this->info("  - Products imported: {$stats['products']['total_products']}");
        $this->info("  - Variants imported: {$stats['products']['total_variants']}");
        $this->info("  - Images imported: {$stats['products']['total_images']}");

        $this->line('');
        $this->info('DRAFT ORDERS:');
        $this->info("  - Draft orders imported: {$stats['draft_orders']['total_draft_orders']}");
        $this->info("  - Line items imported: {$stats['draft_orders']['total_line_items']}");

        if ($stats['total_errors'] > 0) {
            $this->line('');
            $this->error("Tổng số lỗi: {$stats['total_errors']}");

            if (!empty($stats['products']['errors'])) {
                $this->error('Lỗi Products:');
                foreach ($stats['products']['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }

            if (!empty($stats['draft_orders']['errors'])) {
                $this->error('Lỗi Draft Orders:');
                foreach ($stats['draft_orders']['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }

            return 1;
        }

        $this->line('');
        $this->info('✓ Import tất cả dữ liệu hoàn thành thành công!');
        return 0;
    }
}
