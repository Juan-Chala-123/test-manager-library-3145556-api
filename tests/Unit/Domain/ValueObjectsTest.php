<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use Library\Domain\DateRange;
use Library\Domain\DocumentNumber;
use Library\Domain\Isbn;
use Library\Domain\Quantity;
use PHPUnit\Framework\TestCase;

class ValueObjectsTest extends TestCase
{
    public function test_isbn_valid(): void
    {
        $isbn = new Isbn('9780307474728');
        $this->assertEquals('9780307474728', $isbn->value());
        $this->assertEquals('9780307474728', (string)$isbn);
    }

    public function test_isbn_invalid_length(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Isbn('123456789012'); // 12 dígitos
    }

    public function test_isbn_invalid_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Isbn('978030747472A'); // contiene letra
    }

    public function test_quantity_positive(): void
    {
        $quantity = new Quantity(5);
        $this->assertEquals(5, $quantity->value());
        $this->assertEquals('5', (string)$quantity);
    }

    public function test_quantity_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Quantity(0);
    }

    public function test_quantity_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Quantity(-1);
    }

    public function test_daterange_valid(): void
    {
        $start = new \DateTimeImmutable('2026-01-01');
        $end = new \DateTimeImmutable('2026-12-31');
        $range = new DateRange($start, $end);
        $this->assertSame($start, $range->start());
        $this->assertSame($end, $range->end());
        $this->assertTrue($range->contains(new \DateTimeImmutable('2026-06-15')));
    }

    public function test_daterange_invalid_order(): void
    {
        $start = new \DateTimeImmutable('2026-12-31');
        $end = new \DateTimeImmutable('2026-01-01');
        $this->expectException(\InvalidArgumentException::class);
        new DateRange($start, $end);
    }

    public function test_document_number(): void
    {
        $doc = new DocumentNumber('123456789');
        $this->assertEquals('123456789', $doc->value());
        $this->assertEquals('123456789', (string)$doc);
    }

    public function test_value_objects_equality(): void
    {
        $isbn1 = new Isbn('9780307474728');
        $isbn2 = new Isbn('9780307474728');
        $isbn3 = new Isbn('9780307474729');
        
        $this->assertTrue($isbn1->equals($isbn2));
        $this->assertFalse($isbn1->equals($isbn3));

        $qty1 = new Quantity(5);
        $qty2 = new Quantity(5);
        $qty3 = new Quantity(10);
        
        $this->assertTrue($qty1->equals($qty2));
        $this->assertFalse($qty1->equals($qty3));
    }
}
