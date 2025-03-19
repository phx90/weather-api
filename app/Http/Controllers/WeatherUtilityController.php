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
        $params = $request->only(['temperature', 'unit', 'city']);
    
        if (!isset($params['temperature']) || !is_numeric($params['temperature'])) {
            return response()->json(['error' => 'Temperatura inválida'], 400);
        }
    
        if (!isset($params['unit']) || !in_array(strtoupper($params['unit']), ['C', 'F', 'K'])) {
            return response()->json(['error' => 'Unidade inválida'], 400);
        }
    
        $converted = $this->weatherService->convertTemperature($params);
        return response()->json($converted);
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
