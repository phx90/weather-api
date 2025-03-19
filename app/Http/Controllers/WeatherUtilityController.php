<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;

class WeatherUtilityController extends Controller
{
    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function convertTemperature(Request $request)
    {
        $params = $this->validateParams($request, ['temperature', 'unit']);
        $converted = $this->weatherService->convertTemperature($params);

        return $converted
            ? response()->json($converted)
            : response()->json(['error' => 'Temperatura ou unidade inválida'], 400);
    }

    public function compareTemperature(Request $request)
    {
        $params = $this->validateParams($request, ['city', 'lat', 'lon']);
        $comparison = $this->weatherService->compareTemperature($params);

        return $comparison
            ? response()->json($comparison)
            : response()->json(['error' => 'Não foi possível comparar as temperaturas'], 500);
    }

    private function validateParams(Request $request, array $allowedParams)
    {
        return $request->only($allowedParams);
    }
}
