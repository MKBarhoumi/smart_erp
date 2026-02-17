<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;



    public function test_stock_movement_can_be_created(): void
    {
        $product = Product::create([
            'code' => 'PRD-0001',
            'name' => 'Widget A',
            'unit_price' => '25.000',
            'tva_rate' => '19.000',
            'current_stock' => 100,
            'track_inventory' => true,
        ]);

        $user = User::factory()->create();

        $movement = StockMovement::create([
            'product_id' => $product->id,
            'performed_by' => $user->id,
            'type' => 'in',
            'quantity' => 50,
            'stock_before' => '100.000',
            'stock_after' => '150.000',
            'reason' => 'Réapprovisionnement',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 50,
        ]);
    }

    public function test_stock_movement_out(): void
    {
        $product = Product::create([
            'code' => 'PRD-0001',
            'name' => 'Widget A',
            'unit_price' => '25.000',
            'tva_rate' => '19.000',
            'current_stock' => 100,
            'track_inventory' => true,
        ]);

        $user = User::factory()->create();

        StockMovement::create([
            'product_id' => $product->id,
            'performed_by' => $user->id,
            'type' => 'out',
            'quantity' => 10,
            'stock_before' => '100.000',
            'stock_after' => '90.000',
            'reason' => 'Vente facture FAC-001',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'type' => 'out',
            'quantity' => 10,
        ]);
    }

    public function test_stock_movement_belongs_to_product(): void
    {
        $product = Product::create([
            'code' => 'PRD-0001',
            'name' => 'Widget',
            'unit_price' => '10.000',
            'tva_rate' => '19.000',
        ]);

        $user = User::factory()->create();

        $movement = StockMovement::create([
            'product_id' => $product->id,
            'performed_by' => $user->id,
            'type' => 'adjustment',
            'quantity' => 5,
            'stock_before' => '0.000',
            'stock_after' => '5.000',
            'reason' => 'Correction inventaire',
        ]);

        $this->assertEquals($product->id, $movement->product->id);
    }

    public function test_product_has_many_stock_movements(): void
    {
        $product = Product::create([
            'code' => 'PRD-0001',
            'name' => 'Widget',
            'unit_price' => '10.000',
            'tva_rate' => '19.000',
        ]);

        $user = User::factory()->create();

        StockMovement::create([
            'product_id' => $product->id,
            'performed_by' => $user->id,
            'type' => 'in',
            'quantity' => 100,
            'stock_before' => '0.000',
            'stock_after' => '100.000',
            'reason' => 'Initial stock',
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'performed_by' => $user->id,
            'type' => 'out',
            'quantity' => 30,
            'stock_before' => '100.000',
            'stock_after' => '70.000',
            'reason' => 'Sale',
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'performed_by' => $user->id,
            'type' => 'adjustment',
            'quantity' => -5,
            'stock_before' => '70.000',
            'stock_after' => '65.000',
            'reason' => 'Damaged',
        ]);

        $this->assertCount(3, $product->fresh()->stockMovements);
    }
}
