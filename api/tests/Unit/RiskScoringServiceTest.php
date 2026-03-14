<?php

namespace Tests\Unit;

use App\Services\RiskScoringService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RiskScoringServiceTest extends TestCase
{
    #[Test]
    public function it_returns_low_risk_for_zero_score(): void
    {
        $service = new RiskScoringService();

        $result = $service->calculate([
            'pep_status' => false,
            'country_risk' => false,
            'industry_risk' => false,
            'sanctions_check' => false,
        ]);

        $this->assertSame(0, $result['risk_score']);
        $this->assertSame('Low', $result['risk_level']);
    }

    #[Test]
    public function it_returns_medium_risk_at_boundary_score(): void
    {
        $service = new RiskScoringService();

        $result = $service->calculate([
            'pep_status' => true,
            'country_risk' => false,
            'industry_risk' => true,
            'sanctions_check' => false,
        ]);

        $this->assertSame(60, $result['risk_score']);
        $this->assertSame('Medium', $result['risk_level']);
    }

    #[Test]
    public function it_returns_high_risk_above_medium_threshold(): void
    {
        $service = new RiskScoringService();

        $result = $service->calculate([
            'pep_status' => true,
            'country_risk' => true,
            'industry_risk' => false,
            'sanctions_check' => true,
        ]);

        $this->assertSame(120, $result['risk_score']);
        $this->assertSame('High', $result['risk_level']);
    }
}
