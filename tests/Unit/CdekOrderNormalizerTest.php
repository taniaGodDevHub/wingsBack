<?php

declare(strict_types=1);

namespace tests\Unit;

use app\components\cdek\CdekMockData;
use app\components\cdek\CdekOrderNormalizer;
use Codeception\Test\Unit;

final class CdekOrderNormalizerTest extends Unit
{
    public function testNormalizeEntityResponse(): void
    {
        $normalized = CdekOrderNormalizer::normalize(CdekMockData::orderStatus('test-uuid'));

        $this->assertSame('ACCEPTED', $normalized['status']);
        $this->assertSame('10123456789', $normalized['cdek_number']);
        $this->assertSame('Принят складом СДЭК', $normalized['description']);
        $this->assertSame('Москва', $normalized['current_city']);
        $this->assertNotNull($normalized['expected_delivery']);
        $this->assertNull($normalized['delivered_at']);
    }

    public function testNormalizeDeliveredResponse(): void
    {
        $normalized = CdekOrderNormalizer::normalize(CdekMockData::deliveredOrderStatus('test-uuid'));

        $this->assertSame('DELIVERED', $normalized['status']);
        $this->assertNotNull($normalized['delivery_date']);
        $this->assertNotNull($normalized['delivered_at']);
        $this->assertSame($normalized['delivery_date'], $normalized['expected_delivery']);
    }

    public function testNormalizeCreateResponse(): void
    {
        $normalized = CdekOrderNormalizer::normalizeCreateResponse([
            'entity' => [
                'uuid' => 'uuid-1',
                'cdek_number' => '10987654321',
            ],
            'status' => 'ACCEPTED',
        ]);

        $this->assertSame('uuid-1', $normalized['uuid']);
        $this->assertSame('10987654321', $normalized['cdek_number']);
        $this->assertSame('ACCEPTED', $normalized['status']);
    }
}
