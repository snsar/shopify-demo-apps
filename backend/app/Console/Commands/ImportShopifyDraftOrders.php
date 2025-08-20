<?php

namespace App\Console\Commands;

use App\Services\ShopifyImportService;
use Illuminate\Console\Command;

class ImportShopifyDraftOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:import-draft-orders {shop : Shop domain (e.g., myshop.myshopify.com)} {--clear : Clear existing draft orders before import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tất cả draft orders từ Shopify về database';

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

        $this->info("Bắt đầu import draft orders từ shop: {$shop}");

        // Clear existing data if requested
        if ($shouldClear) {
            $this->warn('Đang xóa dữ liệu draft orders hiện có...');
            if ($this->importService->clearShopData($shop)) {
                $this->info('✓ Đã xóa dữ liệu draft orders cũ');
            } else {
                $this->error('✗ Không thể xóa dữ liệu draft orders cũ');
                return 1;
            }
        }

        // Import draft orders
        $this->info('Đang import draft orders...');
        $stats = $this->importService->importDraftOrders($shop);

        // Display results
        $this->line('');
        $this->info('=== KẾT QUẢ IMPORT DRAFT ORDERS ===');
        $this->info("Total draft orders imported: {$stats['total_draft_orders']}");
        $this->info("Total line items imported: {$stats['total_line_items']}");

        if (!empty($stats['errors'])) {
            $this->line('');
            $this->error('Có lỗi xảy ra:');
            foreach ($stats['errors'] as $error) {
                $this->error("- {$error}");
            }
            return 1;
        }

        $this->line('');
        $this->info('✓ Import draft orders hoàn thành thành công!');
        return 0;
    }
}
