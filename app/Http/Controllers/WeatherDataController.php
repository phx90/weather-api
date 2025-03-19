<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;

class WeatherDataController extends Controller
{
    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function currentWeather(Request $request)
    {
        $params = $this->validateParams($request, ['city', 'lat', 'lon']);
        $data = $this->weatherService->getCurrentWeather($params);

        return $data 
            ? response()->json($data)
            : response()->json(['error' => 'Não foi possível obter os dados do clima'], 500);
    }

    public function dailyPhrase(Request $request)
    {
        $params = $this->validateParams($request, ['city']);
        $phrase = $this->weatherService->getDailyPhrase($params);

        return $phrase
            ? response()->json(['frase' => $phrase])
            : response()->json(['error' => 'Não foi possível obter a frase diária'], 500);
    }

    public function forecast7Days(Request $request)
    {
        $params = $this->validateParams($request, ['city', 'lat', 'lon']);
        $forecast = $this->weatherService->get7DaysForecast($params);

        return $forecast
            ? response()->json($forecast)
            : response()->json(['error' => 'Não foi possível obter a previsão dos próximos 7 dias'], 500);
    }

    public function yesterdayAverageTemp(Request $request)
    {
        $params = $this->validateParams($request, ['city', 'lat', 'lon']);
        $average = $this->weatherService->getYesterdayAverageTemp($params);

        return $average
            ? response()->json(['temperaturaMediaOntem' => $average])
            : response()->json(['error' => 'Não foi possível obter os dados históricos'], 500);
    }

    public function sunriseSunset(Request $request)
    {
        $params = $this->validateParams($request, ['city', 'lat', 'lon']);
        $data = $this->weatherService->getSunriseSunset($params);

        return $data
            ? response()->json($data)
            : response()->json(['error' => 'Não foi possível obter os horários de nascer e pôr do sol'], 500);
    }

    public function rainForecast(Request $request)
    {
        $params = $this->validateParams($request, ['city', 'lat', 'lon']);
        $rain = $this->weatherService->getRainForecast($params);

        return $rain
            ? response()->json($rain)
            : response()->json(['error' => 'Não foi possível obter a previsão de chuva'], 500);
    }

    private function validateParams(Request $request, array $allowedParams)
    {
        return $request->only($allowedParams);
    }
}
