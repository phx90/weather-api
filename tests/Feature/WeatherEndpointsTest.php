<?php

namespace Tests\Feature;

use Tests\TestCase;

class WeatherEndpointsTest extends TestCase
{
    public function testCurrentWeatherEndpoint()
    {
        $this->get('/api/weather/current?city=New%20York');
        $this->seeStatusCode(200);
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
        $this->get('/api/weather/daily-phrase?city=Los%20Angeles');
        $this->seeStatusCode(200);
        $this->seeJsonStructure(['phrase']);
    }

    public function testForecastEndpoint()
    {
        $this->get('/api/weather/forecast?city=Chicago');
        $this->seeStatusCode(200);
        $this->seeJsonStructure([
            'city',
            'forecast' => [
                '*' => [
                    'date',
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

    public function testConvertTemperatureEndpoint()
    {
        $this->get('/api/weather/convert?temperature=25&unit=F');
        $this->seeStatusCode(200);
        $this->seeJsonStructure([
            'originalTemperature',
            'convertedTemperature',
            'unit'
        ]);
    }
}
