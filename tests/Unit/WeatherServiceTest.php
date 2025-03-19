<?php

namespace Tests\Unit;

use App\Services\WeatherService;
use Tests\TestCase;
use ReflectionMethod;

class WeatherServiceTest extends TestCase
{
    protected WeatherService $weatherService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->weatherService = new WeatherService();
    }

    public function testSimplifyCityNameReturnsOnlyCity()
    {
        $displayName = "New York, New York County, New York, USA";
        $refMethod = new ReflectionMethod($this->weatherService, 'simplifyCityName');
        $refMethod->setAccessible(true);
        $result = $refMethod->invoke($this->weatherService, $displayName);
        $this->assertEquals("New York", $result);
    }

    public function testGetCoordinatesReturnsDefaultsWhenNoParams()
    {
        $params = [];
        $result = $this->weatherService->getCoordinates($params);
        $this->assertArrayHasKey('lat', $result);
        $this->assertArrayHasKey('lon', $result);
        $this->assertArrayHasKey('city', $result);
        $this->assertEquals('Brasilia', $result['city']);
    }
}
