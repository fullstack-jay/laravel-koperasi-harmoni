<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\V1\Stock\Models\StockItem;

class SIMLKDMasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds for master data.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $this->seedStockItems();

            DB::commit();
            $this->command->info('✅ SIM-LKD Master Data seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Failed to seed master data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Seed sample stock items
     */
    private function seedStockItems(): void
    {
        $stockItems = [
            // Beras (Rice)
            [
                'code' => 'STK-001',
                'name' => 'Beras Premium 25kg',
                'category' => 'Beras',
                'unit' => 'karung',
                'min_stock' => 50,
                'buy_price' => 150000,
                'sell_price' => 165000,
            ],
            [
                'code' => 'STK-002',
                'name' => 'Beras Medium 25kg',
                'category' => 'Beras',
                'unit' => 'karung',
                'min_stock' => 50,
                'buy_price' => 135000,
                'sell_price' => 148000,
            ],
            [
                'code' => 'STK-003',
                'name' => 'Beras Wangi 5kg',
                'category' => 'Beras',
                'unit' => 'karung',
                'min_stock' => 100,
                'buy_price' => 35000,
                'sell_price' => 40000,
            ],
            // Minyak (Oil)
            [
                'code' => 'STK-004',
                'name' => 'Minyak Goreng 2L',
                'category' => 'Minyak',
                'unit' => 'botol',
                'min_stock' => 60,
                'buy_price' => 28000,
                'sell_price' => 32000,
            ],
            [
                'code' => 'STK-005',
                'name' => 'Minyak Goreng 5L',
                'category' => 'Minyak',
                'unit' => 'jerigen',
                'min_stock' => 30,
                'buy_price' => 65000,
                'sell_price' => 75000,
            ],
            // Tepung (Flour)
            [
                'code' => 'STK-006',
                'name' => 'Tepung Terigu 25kg',
                'category' => 'Tepung',
                'unit' => 'karung',
                'min_stock' => 40,
                'buy_price' => 120000,
                'sell_price' => 135000,
            ],
            [
                'code' => 'STK-007',
                'name' => 'Tepung Beras 1kg',
                'category' => 'Tepung',
                'unit' => 'bungkus',
                'min_stock' => 200,
                'buy_price' => 8000,
                'sell_price' => 10000,
            ],
            // Gula (Sugar)
            [
                'code' => 'STK-008',
                'name' => 'Gula Pasir 1kg',
                'category' => 'Gula',
                'unit' => 'kg',
                'min_stock' => 100,
                'buy_price' => 14000,
                'sell_price' => 16000,
            ],
            [
                'code' => 'STK-009',
                'name' => 'Gula Merah 1kg',
                'category' => 'Gula',
                'unit' => 'kg',
                'min_stock' => 50,
                'buy_price' => 12500,
                'sell_price' => 14500,
            ],
            // Bumbu (Spices)
            [
                'code' => 'STK-010',
                'name' => 'Garam 500gr',
                'category' => 'Bumbu',
                'unit' => 'bungkus',
                'min_stock' => 150,
                'buy_price' => 3000,
                'sell_price' => 4000,
            ],
            [
                'code' => 'STK-011',
                'name' => 'Bawang Merah 250gr',
                'category' => 'Bumbu',
                'unit' => 'gram',
                'min_stock' => 100,
                'buy_price' => 12000,
                'sell_price' => 15000,
            ],
            [
                'code' => 'STK-012',
                'name' => 'Bawang Putih 250gr',
                'category' => 'Bumbu',
                'unit' => 'gram',
                'min_stock' => 100,
                'buy_price' => 18000,
                'sell_price' => 22000,
            ],
            [
                'code' => 'STK-013',
                'name' => 'Cabai Merah Keriting 250gr',
                'category' => 'Bumbu',
                'unit' => 'gram',
                'min_stock' => 80,
                'buy_price' => 15000,
                'sell_price' => 19000,
            ],
            // Telur (Eggs)
            [
                'code' => 'STK-014',
                'name' => 'Telur Ayam 1kg (±16 butir)',
                'category' => 'Telur',
                'unit' => 'kg',
                'min_stock' => 60,
                'buy_price' => 28000,
                'sell_price' => 32000,
            ],
            // Sayuran (Vegetables)
            [
                'code' => 'STK-015',
                'name' => 'Kentang 1kg',
                'category' => 'Sayuran',
                'unit' => 'kg',
                'min_stock' => 50,
                'buy_price' => 12000,
                'sell_price' => 15000,
            ],
            [
                'code' => 'STK-016',
                'name' => 'Wortel 1kg',
                'category' => 'Sayuran',
                'unit' => 'kg',
                'min_stock' => 40,
                'buy_price' => 10000,
                'sell_price' => 13000,
            ],
            [
                'code' => 'STK-017',
                'name' => 'Bawang Bombay 1kg',
                'category' => 'Sayuran',
                'unit' => 'kg',
                'min_stock' => 30,
                'buy_price' => 25000,
                'sell_price' => 30000,
            ],
            // Dagingan (Meat)
            [
                'code' => 'STK-018',
                'name' => 'Ayam Potong 1kg',
                'category' => 'Dagingan',
                'unit' => 'kg',
                'min_stock' => 20,
                'buy_price' => 35000,
                'sell_price' => 40000,
            ],
            [
                'code' => 'STK-019',
                'name' => 'Daging Sapi 1kg',
                'category' => 'Dagingan',
                'unit' => 'kg',
                'min_stock' => 15,
                'buy_price' => 120000,
                'sell_price' => 140000,
            ],
            // Lainnya (Others)
            [
                'code' => 'STK-020',
                'name' => 'Kecap Manis 500ml',
                'category' => 'Lainnya',
                'unit' => 'botol',
                'min_stock' => 120,
                'buy_price' => 8000,
                'sell_price' => 10000,
            ],
            [
                'code' => 'STK-021',
                'name' => 'Saus Tomat 500ml',
                'category' => 'Lainnya',
                'unit' => 'botol',
                'min_stock' => 80,
                'buy_price' => 12000,
                'sell_price' => 15000,
            ],
        ];

        $now = now();
        foreach ($stockItems as $item) {
            StockItem::firstOrCreate(
                ['code' => $item['code']],
                array_merge($item, [
                    'current_stock' => 0,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->command->info('✅ Seeded ' . count($stockItems) . ' stock items');
    }
}
