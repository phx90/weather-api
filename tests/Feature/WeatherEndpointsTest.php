<?php

namespace Tests\Feature;

use Tests\TestCase;

class WeatherEndpointsTest extends TestCase
{
    public function testCurrentWeatherEndpoint()
    {
        $this->get('/api/weather/current?city=Brasilia');
        $this->assertResponseStatus(200);
        $this->seeJsonStructure([
            'city',
            'latitude',
            'longitude',
            'temperature',
            'humidity',
            'description',
            'icon',
            'time'
        ]);
    }

    public function testDailyPhraseEndpoint()
    {
        $this->get('/api/weather/daily-phrase?city=Brasilia');
        $this->assertResponseStatus(200);
        $this->seeJsonStructure(['frase']);
    }

    public function testForecast7DaysEndpoint()
    {
        $this->get('/api/weather/forecast?city=Brasilia');
        $this->assertResponseStatus(200);
        $this->seeJsonStructure([
            'city',
            'forecast' => [
                '*' => [
                    'date',
                    'weekday',
                    'temperature_max',
                    'temperature_min',
                    'description',
                    'icon',
                    'sunrise',
                    'sunset'
                ]
            ]
        ]);
    }

    public function testYesterdayAverageEndpoint()
    {
        $this->get('/api/weather/yesterday-average?city=Brasilia');
        $this->assertResponseStatus(200);
        $this->seeJsonStructure(['temperaturaMediaOntem']);
    }

    public function testConvertTemperatureEndpointValid()
    {
        $this->get('/api/weather/convert?temperature=25&unit=F');
        $this->assertResponseStatus(200);
        $this->seeJsonStructure(['originalTemperature', 'convertedTemperature', 'unit']);
    }

    public function testConvertTemperatureEndpointInvalid()
    {
        $this->get('/api/weather/convert?temperature=abc&unit=F');
        $this->assertResponseStatus(400);
        $this->seeJsonStructure(['error']);
    }

    public function testSunriseSunsetEndpoint()
    {
        $this->get('/api/weather/sunrise-sunset?city=Brasilia');
        $this->assertResponseStatus(200);
        $this->seeJsonStructure(['sunrise', 'sunset']);
    }

    public function testRainForecastEndpoint()
    {
        $this->get('/api/weather/rain-forecast?city=Brasilia');
        $this->assertResponseStatus(200);
        $this->seeJsonStructure(['city', 'rainForecast']);
    }

    public function testCompareTemperatureEndpoint()
    {
        $this->get('/api/weather/compare?city=Brasilia');
        $this->assertResponseStatus(200);
        $this->seeJsonStructure(['city', 'todayTemperature', 'yesterdayAverageTemperature', 'comparison']);
    }
}
