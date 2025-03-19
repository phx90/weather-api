<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function currentWeather(Request $request)
    {
        $params = $request->all();
        $data = $this->weatherService->getCurrentWeather($params);
        if (!$data) {
            return response()->json(['error' => 'Unable to fetch weather data'], 500);
        }
        return response()->json($data);
    }

    public function dailyPhrase(Request $request)
    {
        $params = $request->all();
        $phrase = $this->weatherService->getDailyPhrase($params);
        if (!$phrase) {
            return response()->json(['error' => 'Unable to fetch daily phrase'], 500);
        }
        return response()->json(['phrase' => $phrase]);
    }

    public function forecast7Days(Request $request)
    {
        $params = $request->all();
        $forecast = $this->weatherService->get7DaysForecast($params);
        if (!$forecast) {
            return response()->json(['error' => 'Unable to fetch forecast data'], 500);
        }
        return response()->json($forecast);
    }

    public function yesterdayAverageTemp(Request $request)
    {
        $params = $request->all();
        $average = $this->weatherService->getYesterdayAverageTemp($params);
        if (!$average) {
            return response()->json(['error' => 'Unable to fetch historical data'], 500);
        }
        return response()->json(['yesterdayAverageTemperature' => $average]);
    }

    public function convertTemperature(Request $request)
    {
        $params = $request->all();
        $converted = $this->weatherService->convertTemperature($params);
        if (!$converted) {
            return response()->json(['error' => 'Invalid temperature or unit'], 400);
        }
        return response()->json($converted);
    }

    public function sunriseSunset(Request $request)
    {
        $params = $request->all();
        $data = $this->weatherService->getSunriseSunset($params);
        if (!$data) {
            return response()->json(['error' => 'Unable to fetch sunrise/sunset data'], 500);
        }
        return response()->json($data);
    }

    public function rainForecast(Request $request)
    {
        $params = $request->all();
        $rain = $this->weatherService->getRainForecast($params);
        if (!$rain) {
            return response()->json(['error' => 'Unable to fetch rain forecast'], 500);
        }
        return response()->json($rain);
    }

    public function compareTemperature(Request $request)
    {
        $params = $request->all();
        $comparison = $this->weatherService->compareTemperature($params);
        if (!$comparison) {
            return response()->json(['error' => 'Unable to compare temperatures'], 500);
        }
        return response()->json($comparison);
    }
}
