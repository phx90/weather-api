<?php

namespace Tests\Unit;

use App\Services\WeatherService;
use Illuminate\Support\Facades\Http;
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

    public function testGeocodeCityReturnsData()
    {
        Http::fake([
            'https://geocoding-api.open-meteo.com/v1/search*' => Http::response([
                'results' => [
                    [
                        'latitude'  => -15.7801,
                        'longitude' => -47.9292,
                        'name'      => 'Brasília'
                    ]
                ]
            ], 200)
        ]);
        $result = $this->weatherService->geocodeCity("Brasilia");
        $this->assertNotNull($result);
        $this->assertEquals(-15.7801, $result['lat']);
        $this->assertEquals(-47.9292, $result['lon']);
        $this->assertEquals('Brasília', $result['city']);
    }

    public function testGetCoordinatesWithCityParam()
    {
        Http::fake([
            'https://geocoding-api.open-meteo.com/v1/search*' => Http::response([
                'results' => [
                    [
                        'latitude'  => -15.7801,
                        'longitude' => -47.9292,
                        'name'      => 'Brasília'
                    ]
                ]
            ], 200)
        ]);
        $params = ['city' => 'Brasilia'];
        $result = $this->weatherService->getCoordinates($params);
        $this->assertEquals('Brasília', $result['city']);
    }

    public function testGetCoordinatesWithLatLon()
    {
        $params = ['lat' => -10.0, 'lon' => -50.0];
        $result = $this->weatherService->getCoordinates($params);
        $this->assertEquals(-10.0, $result['lat']);
        $this->assertEquals(-50.0, $result['lon']);
        $this->assertEquals("Brasilia", $result['city']);
    }

    public function testSimplifyCityName()
    {
        $refMethod = new ReflectionMethod($this->weatherService, 'simplifyCityName');
        $refMethod->setAccessible(true);
        $result = $refMethod->invoke($this->weatherService, "New York, New York County, New York, USA");
        $this->assertEquals("New York", $result);
    }
}
