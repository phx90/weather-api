<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use IntlDateFormatter;

class WeatherService
{
    public function geocodeCity($city)
    {
        $city = ucwords(mb_strtolower(trim($city)));
        $url = "https://geocoding-api.open-meteo.com/v1/search";
        $response = Http::get($url, ['name' => $city, 'count' => 1]);
        $data = $response->json();
        if (empty($data) || !isset($data['results'][0])) return null;
        $result = $data['results'][0];
        return [
            'lat'  => $result['latitude'],
            'lon'  => $result['longitude'],
            'city' => $result['name']
        ];
    }

    public function getCoordinates(array $params)
    {
        $inputCity = isset($params['city']) ? urldecode($params['city']) : "";
        if (!empty($inputCity)) {
            $geo = $this->geocodeCity($inputCity);
            if ($geo) return $geo;
        }
        $latitude = $params['lat'] ?? null;
        $longitude = $params['lon'] ?? null;
        $city = !empty($inputCity) ? $inputCity : "Brasilia";
        if (!$latitude || !$longitude) return [
            'lat'  => env('DEFAULT_LATITUDE', -15.7801),
            'lon'  => env('DEFAULT_LONGITUDE', -47.9292),
            'city' => $city
        ];
        return [
            'lat'  => $latitude,
            'lon'  => $longitude,
            'city' => $city
        ];
    }

    private function findClosestTimeIndex(array $times, $targetTime)
    {
        $targetTimestamp = strtotime($targetTime);
        $closestIndex = null;
        $smallestDiff = PHP_INT_MAX;
        foreach ($times as $i => $time) {
            $diff = abs(strtotime($time) - $targetTimestamp);
            if ($diff < $smallestDiff) {
                $smallestDiff = $diff;
                $closestIndex = $i;
            }
        }
        return $closestIndex;
    }

    public function mapWeatherCode($code)
    {
        $mapping = [
            0  => ['description' => 'Clear sky', 'icon' => '☀️'],
            1  => ['description' => 'Mainly clear', 'icon' => '🌤'],
            2  => ['description' => 'Partly cloudy', 'icon' => '⛅️'],
            3  => ['description' => 'Overcast', 'icon' => '☁️'],
            45 => ['description' => 'Fog', 'icon' => '🌫'],
            48 => ['description' => 'Depositing rime fog', 'icon' => '🌫'],
            51 => ['description' => 'Light drizzle', 'icon' => '🌦'],
            53 => ['description' => 'Moderate drizzle', 'icon' => '🌦'],
            55 => ['description' => 'Dense drizzle', 'icon' => '🌦'],
            56 => ['description' => 'Light freezing drizzle', 'icon' => '🌧'],
            57 => ['description' => 'Dense freezing drizzle', 'icon' => '🌧'],
            61 => ['description' => 'Slight rain', 'icon' => '🌧'],
            63 => ['description' => 'Moderate rain', 'icon' => '🌧'],
            65 => ['description' => 'Heavy rain', 'icon' => '🌧'],
            66 => ['description' => 'Light freezing rain', 'icon' => '🌧'],
            67 => ['description' => 'Heavy freezing rain', 'icon' => '🌧'],
            71 => ['description' => 'Slight snow fall', 'icon' => '🌨'],
            73 => ['description' => 'Moderate snow fall', 'icon' => '🌨'],
            75 => ['description' => 'Heavy snow fall', 'icon' => '🌨'],
            77 => ['description' => 'Snow grains', 'icon' => '🌨'],
            80 => ['description' => 'Slight rain showers', 'icon' => '🌧'],
            81 => ['description' => 'Moderate rain showers', 'icon' => '🌧'],
            82 => ['description' => 'Violent rain showers', 'icon' => '🌧'],
            85 => ['description' => 'Slight snow showers', 'icon' => '🌨'],
            86 => ['description' => 'Heavy snow showers', 'icon' => '🌨'],
            95 => ['description' => 'Thunderstorm', 'icon' => '⛈'],
            96 => ['description' => 'Thunderstorm with slight hail', 'icon' => '⛈'],
            99 => ['description' => 'Thunderstorm with heavy hail', 'icon' => '⛈']
        ];
        return $mapping[$code] ?? ['description' => 'Unknown', 'icon' => '❓'];
    }

    protected function simplifyCityName($displayName)
    {
        $parts = array_map('trim', explode(',', $displayName));
        return $parts[0] ?? $displayName;
    }

    protected function translateDescription($description)
    {
        $translations = [
            'Clear sky' => 'ensolarado',
            'Mainly clear' => 'predominantemente limpo',
            'Partly cloudy' => 'parcialmente nublado',
            'Overcast' => 'encoberto',
            'Fog' => 'com neblina',
            'Depositing rime fog' => 'com neblina',
            'Light drizzle' => 'com chuvisco leve',
            'Moderate drizzle' => 'com chuvisco moderado',
            'Dense drizzle' => 'com chuvisco intenso',
            'Light freezing drizzle' => 'com chuvisco congelante leve',
            'Dense freezing drizzle' => 'com chuvisco congelante intenso',
            'Slight rain' => 'com chuva leve',
            'Moderate rain' => 'com chuva moderada',
            'Heavy rain' => 'com chuva forte',
            'Light freezing rain' => 'com chuva congelante leve',
            'Heavy freezing rain' => 'com chuva congelante forte',
            'Slight snow fall' => 'com neve leve',
            'Moderate snow fall' => 'com neve moderada',
            'Heavy snow fall' => 'com neve forte',
            'Snow grains' => 'com granizo',
            'Slight rain showers' => 'com pancadas de chuva leves',
            'Moderate rain showers' => 'com pancadas de chuva moderadas',
            'Violent rain showers' => 'com pancadas de chuva intensas',
            'Slight snow showers' => 'com pancadas de neve leves',
            'Heavy snow showers' => 'com pancadas de neve fortes',
            'Thunderstorm' => 'com tempestade',
            'Thunderstorm with slight hail' => 'com tempestade e granizo leve',
            'Thunderstorm with heavy hail' => 'com tempestade e granizo forte'
        ];
        return $translations[$description] ?? $description;
    }

    public function getCurrentWeather(array $params)
    {
        $coords = $this->getCoordinates($params);
        $lat = $coords['lat'];
        $lon = $coords['lon'];
        $normalizedCity = $coords['city'];
        $apiUrl = env('API_BASE_URL');
        $query = [
            'latitude'         => $lat,
            'longitude'        => $lon,
            'current_weather'  => 'true',
            'hourly'           => 'relativehumidity_2m',
            'temperature_unit' => 'celsius',
            'timezone'         => 'auto'
        ];
        $response = Http::get($apiUrl, $query);
        $data = $response->json();
        if (!isset($data['current_weather'])) return null;
        $current = $data['current_weather'];
        $humidity = null;
        if (isset($data['hourly']['time'], $data['hourly']['relativehumidity_2m'])) {
            $index = $this->findClosestTimeIndex($data['hourly']['time'], $current['time']);
            if ($index !== null) $humidity = $data['hourly']['relativehumidity_2m'][$index];
        }
        $weatherCode = array_key_exists('weathercode', $current) ? $current['weathercode'] : null;
        $mapping = $weatherCode !== null ? $this->mapWeatherCode($weatherCode) : ['description' => 'Desconhecido', 'icon' => '❓'];
        $icon = $mapping['icon'];
        if (isset($current['is_day']) && $current['is_day'] == 0 && $icon === '☀️') $icon = '🌙';
        return [
            'city' => $normalizedCity,
            'latitude' => $lat,
            'longitude' => $lon,
            'temperature' => $current['temperature'],
            'humidity' => $humidity,
            'description' => $this->translateDescription($mapping['description']),
            'icon' => $icon,
            'time' => $current['time']
        ];
    }

    public function get7DaysForecast(array $params)
    {
        $coords = $this->getCoordinates($params);
        $lat = $coords['lat'];
        $lon = $coords['lon'];
        $normalizedCity = $coords['city'];
        $apiUrl = env('API_BASE_URL');
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+6 days'));
        $query = [
            'latitude'   => $lat,
            'longitude'  => $lon,
            'daily'      => 'temperature_2m_max,temperature_2m_min,weathercode,sunrise,sunset',
            'timezone'   => 'auto',
            'start_date' => $startDate,
            'end_date'   => $endDate
        ];
        $response = Http::get($apiUrl, $query);
        $data = $response->json();
        if (!isset($data['daily'])) return null;
        $daily = $data['daily'];
        $forecast = [];
        $daysCount = count($daily['time']);
        $formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
        foreach ($daily['time'] as $i => $date) {
            $mapping = $this->mapWeatherCode($daily['weathercode'][$i]);
            $weekday = $formatter->format(strtotime($date));
            $forecast[] = [
                'date' => $date,
                'weekday' => ucwords($weekday),
                'temperature_max' => $daily['temperature_2m_max'][$i],
                'temperature_min' => $daily['temperature_2m_min'][$i],
                'description' => $this->translateDescription($mapping['description']),
                'icon' => $mapping['icon'],
                'sunrise' => $daily['sunrise'][$i],
                'sunset' => $daily['sunset'][$i]
            ];
        }
        return ['city' => $normalizedCity, 'forecast' => $forecast];
    }

    public function getYesterdayAverageTemp(array $params)
    {
        $coords = $this->getCoordinates($params);
        $lat = $coords['lat'];
        $lon = $coords['lon'];
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $apiUrl = env('API_BASE_URL');
        $query = [
            'latitude'   => $lat,
            'longitude'  => $lon,
            'daily'      => 'temperature_2m_max,temperature_2m_min',
            'timezone'   => 'auto',
            'start_date' => $yesterday,
            'end_date'   => $yesterday
        ];
        $response = Http::get($apiUrl, $query);
        $data = $response->json();
        if (!isset($data['daily'])) return null;
        $daily = $data['daily'];
        $avgTemp = ($daily['temperature_2m_max'][0] + $daily['temperature_2m_min'][0]) / 2;
        return round($avgTemp) . '°C';
    }

    public function convertTemperature(array $params)
    {
        $temperature = $params['temperature'] ?? null;
        $unit = strtoupper($params['unit'] ?? 'C');
        if (!is_numeric($temperature)) return null;
        switch ($unit) {
            case 'F':
                $converted = ($temperature * 9/5) + 32;
                break;
            case 'K':
                $converted = $temperature + 273.15;
                break;
            case 'C':
                $converted = $temperature;
                break;
            default:
                return null;
        }
        return ['originalTemperature' => $temperature . '°C', 'convertedTemperature' => $converted, 'unit' => $unit];
    }

    public function getSunriseSunset(array $params)
    {
        $coords = $this->getCoordinates($params);
        $lat = $coords['lat'];
        $lon = $coords['lon'];
        $today = date('Y-m-d');
        $apiUrl = env('API_BASE_URL');
        $query = [
            'latitude'   => $lat,
            'longitude'  => $lon,
            'daily'      => 'sunrise,sunset',
            'timezone'   => 'auto',
            'start_date' => $today,
            'end_date'   => $today
        ];
        $response = Http::get($apiUrl, $query);
        $data = $response->json();
        if (!isset($data['daily'])) return null;
        $daily = $data['daily'];
        return ['sunrise' => $daily['sunrise'][0], 'sunset' => $daily['sunset'][0]];
    }

    public function getRainForecast(array $params)
    {
        $forecastData = $this->get7DaysForecast($params);
        if (!$forecastData) return null;
        $forecast = $forecastData['forecast'];
        $rainConditions = [
            'com chuvisco leve',
            'com chuvisco moderado',
            'com chuvisco intenso',
            'com chuva leve',
            'com chuva moderada',
            'com chuva forte',
            'com pancadas de chuva leves',
            'com pancadas de chuva moderadas',
            'com pancadas de chuva intensas'
        ];
        $rainDays = [];
        foreach ($forecast as $day) {
            if (in_array($day['description'], $rainConditions)) $rainDays[] = $day['date'];
        }
        if (count($rainDays) > 0) return [
            'city' => $forecastData['city'],
            'rainForecast' => 'Previsão de chuva para os dias: ' . implode(', ', $rainDays) . '.'
        ];
        return [
            'city' => $forecastData['city'],
            'rainForecast' => 'Não há previsão de chuva para os próximos 7 dias.'
        ];
    }

    public function compareTemperature(array $params)
    {
        $current = $this->getCurrentWeather($params);
        if (!$current) return null;
        $yesterdayAvg = str_replace('°C', '', $this->getYesterdayAverageTemp($params));
        $todayTemp = $current['temperature'];
        if ($todayTemp > $yesterdayAvg) return [
            'city' => $current['city'],
            'todayTemperature' => $todayTemp . '°C',
            'yesterdayAverageTemperature' => $yesterdayAvg . '°C',
            'comparison' => "Hoje está mais quente do que ontem."
        ];
        if ($todayTemp < $yesterdayAvg) return [
            'city' => $current['city'],
            'todayTemperature' => $todayTemp . '°C',
            'yesterdayAverageTemperature' => $yesterdayAvg . '°C',
            'comparison' => "Hoje está mais frio do que ontem."
        ];
        return [
            'city' => $current['city'],
            'todayTemperature' => $todayTemp . '°C',
            'yesterdayAverageTemperature' => $yesterdayAvg . '°C',
            'comparison' => "A temperatura de hoje é igual à de ontem."
        ];
    }

    public function getDailyPhrase(array $params)
    {
        $current = $this->getCurrentWeather($params);
        if (!$current) return null;
        $city = $current['city'];
        $temp = round($current['temperature']);
        $humidity = $current['humidity'];
        $desc = $current['description'];
        $phrase = "Hoje está {$desc} em {$city}, com {$temp}°C";
        if ($humidity !== null) return $phrase . " e umidade de {$humidity}%. Aproveite o dia!";
        return $phrase . ". Aproveite o dia!";
    }
}
