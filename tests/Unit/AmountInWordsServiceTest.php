<?php

namespace Tests\Unit;

use App\Services\AmountInWordsService;
use PHPUnit\Framework\TestCase;

class AmountInWordsServiceTest extends TestCase
{
    private AmountInWordsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AmountInWordsService();
    }

    public function test_zero_amount(): void
    {
        $result = $this->service->convert('0.000');
        $this->assertStringContainsString('zero', strtolower($result));
    }

    public function test_integer_amount(): void
    {
        $result = $this->service->convert('100.000');
        $this->assertStringContainsString('cent', strtolower($result));
        $this->assertStringContainsString('dinar', strtolower($result));
    }

    public function test_amount_with_millimes(): void
    {
        $result = $this->service->convert('1.500');
        $this->assertStringContainsString('dinar', strtolower($result));
        $this->assertStringContainsString('millime', strtolower($result));
    }

    public function test_large_amount(): void
    {
        $result = $this->service->convert('1234567.890');
        $this->assertStringContainsString('million', strtolower($result));
        $this->assertStringContainsString('dinar', strtolower($result));
    }

    public function test_french_70s_rule(): void
    {
        // 70 = soixante-dix in French
        $result = $this->service->convert('70.000');
        $this->assertStringContainsString('soixante', strtolower($result));
    }

    public function test_french_80s_rule(): void
    {
        // 80 = quatre vingts in French
        $result = $this->service->convert('80.000');
        $this->assertStringContainsString('quatre vingt', strtolower($result));
    }

    public function test_french_90s_rule(): void
    {
        // 90 = quatre vingt dix in French
        $result = $this->service->convert('90.000');
        $this->assertStringContainsString('quatre vingt', strtolower($result));
    }

    public function test_one_dinar(): void
    {
        $result = $this->service->convert('1.000');
        $this->assertStringContainsString('un dinar', strtolower($result));
    }

    public function test_two_dinars_plural(): void
    {
        $result = $this->service->convert('2.000');
        $this->assertStringContainsString('dinars', strtolower($result));
    }

    public function test_decimal_only(): void
    {
        $result = $this->service->convert('0.150');
        $this->assertStringContainsString('millime', strtolower($result));
    }
}
