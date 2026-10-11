<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductDescriptionTableTest extends TestCase
{
    public function test_pipe_format_becomes_table_rows(): void
    {
        $parsed = parse_product_description_rows("Display | 6.1 OLED\nBattery | 4000 mAh");

        $this->assertSame('', $parsed['intro']);
        $this->assertCount(2, $parsed['rows']);
        $this->assertSame('Display', $parsed['rows'][0]['heading']);
        $this->assertSame('6.1 OLED', $parsed['rows'][0]['detail']);
    }

    public function test_section_blocks_become_tabular_rows(): void
    {
        $text = <<<TXT
Xiaomi Redmi Note 17 Pro Max 5G — Specifications

Display
6.83-inch AMOLED display
120Hz refresh rate

Performance
Qualcomm Snapdragon 8 Gen 5
8GB / 12GB RAM

Camera
50MP main camera
32MP front camera
TXT;

        $parsed = parse_product_description_rows($text);

        $this->assertStringContainsString('Xiaomi Redmi Note 17 Pro Max 5G', $parsed['intro']);
        $this->assertGreaterThanOrEqual(3, count($parsed['rows']));

        $byHeading = collect($parsed['rows'])->keyBy('heading');
        $this->assertTrue($byHeading->has('Display'));
        $this->assertStringContainsString('6.83-inch', $byHeading['Display']['detail']);
        $this->assertStringContainsString('120Hz', $byHeading['Display']['detail']);
        $this->assertTrue($byHeading->has('Performance'));
        $this->assertStringContainsString('Snapdragon', $byHeading['Performance']['detail']);
        $this->assertTrue($byHeading->has('Camera'));
    }

    public function test_colon_line_becomes_row(): void
    {
        $parsed = parse_product_description_rows("Battery: 5000 mAh\nWeight: 199 g");

        $this->assertCount(2, $parsed['rows']);
        $this->assertSame('Battery', $parsed['rows'][0]['heading']);
        $this->assertSame('5000 mAh', $parsed['rows'][0]['detail']);
    }
}
