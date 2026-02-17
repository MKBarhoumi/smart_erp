<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;



    public function test_product_can_be_created(): void
    {
        $product = Product::create([
            'code' => 'PRD-0001',
            'name' => 'Service Consultation',
            'description' => 'Consultation informatique',
            'unit_price' => '150.000',
            'tva_rate' => '19.000',
            'unit_of_measure' => 'H',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('products', [
            'code' => 'PRD-0001',
            'name' => 'Service Consultation',
        ]);
    }

    public function test_product_code_must_be_unique(): void
    {
        Product::create([
            'code' => 'PRD-0001',
            'name' => 'Product A',
            'unit_price' => '100.000',
            'tva_rate' => '19.000',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Product::create([
            'code' => 'PRD-0001',
            'name' => 'Product B',
            'unit_price' => '200.000',
            'tva_rate' => '19.000',
        ]);
    }

    public function test_product_can_be_updated(): void
    {
        $product = Product::create([
            'code' => 'PRD-0001',
            'name' => 'Old Name',
            'unit_price' => '100.000',
            'tva_rate' => '19.000',
        ]);

        $product->update([
            'name' => 'New Name',
            'unit_price' => '200.000',
        ]);

        $this->assertEquals('New Name', $product->fresh()->name);
        $this->assertEquals('200.000', $product->fresh()->unit_price);
    }

    public function test_product_can_be_soft_managed(): void
    {
        $product = Product::create([
            'code' => 'PRD-0001',
            'name' => 'Test',
            'unit_price' => '100.000',
            'tva_rate' => '19.000',
            'is_active' => true,
        ]);

        $product->update(['is_active' => false]);

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_product_has_stock_movements(): void
    {
        $product = Product::create([
            'code' => 'PRD-0001',
            'name' => 'Test',
            'unit_price' => '100.000',
            'tva_rate' => '19.000',
        ]);

        $this->assertCount(0, $product->stockMovements);
    }

    public function test_product_default_values(): void
    {
        $product = Product::create([
            'code' => 'PRD-0001',
            'name' => 'Test',
            'unit_price' => '50.000',
            'tva_rate' => '19.000',
        ]);

        $product->refresh();

        $this->assertNotNull($product->created_at);
    }

    public function test_product_tva_rate_values(): void
    {
        foreach (['0.000', '7.000', '13.000', '19.000'] as $rate) {
            $product = Product::create([
                'code' => "PRD-{$rate}",
                'name' => "Product TVA {$rate}",
                'unit_price' => '100.000',
                'tva_rate' => $rate,
            ]);

            $this->assertEquals($rate, $product->tva_rate);
        }
    }
}
