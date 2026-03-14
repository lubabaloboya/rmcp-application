<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiNotFoundResponseTest extends TestCase
{
    #[Test]
    public function unknown_api_route_returns_json_not_found_shape(): void
    {
        $response = $this->getJson('/api/v1/does-not-exist');

        $response
            ->assertStatus(404)
            ->assertJson([
                'message' => 'Resource not found.',
            ]);
    }
}
