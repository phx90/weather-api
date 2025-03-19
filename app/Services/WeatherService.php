<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WeatherService
{
    public function geocodeCity($city)
    {
        $city = ucwords(mb_strtolower(trim($city)));
        $url = "https://nominatim.openstreetmap.org/search";
        $response = Http::withHeaders([
            'User-Agent' => 'WeatherAPI/1.0 (seu-email@exemplo.com)'
        ])->get($url, [
            'format'         => 'json',
            'q'              => $city,
            'limit'          => 1,
            'addressdetails' => 1
        ]);
        $data = $response->json();
        if (empty($data) || !isset($data[0]['lat']) || !isset($data[0]['lon'])) {
            return null;
        }
        $address = $data[0]['address'] ?? [];
        $normalizedCity = null;
        if (isset($address['municipality'])) {
            $normalizedCity = $address['municipality'];
        }
        if (!$normalizedCity && isset($address['city'])) {
            $normalizedCity = $address['city'];
        }
        if (!$normalizedCity && isset($address['town'])) {
            $normalizedCity = $address['town'];
        }
        if (!$normalizedCity && isset($address['village'])) {
            $normalizedCity = $address['village'];
        }
        if (!$normalizedCity) {
            $normalizedCity = $this->simplifyCityName($data[0]['display_name']);
        }
        return [
            'lat'  => $data[0]['lat'],
            'lon'  => $data[0]['lon'],
            'city' => $normalizedCity
        ];
    }
    
      

    private function simplifyCityName($displayName)
    {
        $parts = array_map('trim', explode(',', $displayName));
        return $parts[0] ?? $displayName;
    }

    public function getCoordinates(array $params)
    {
        if (isset($params['city']) && !empty($params['city'])) {
            $geo = $this->geocodeCity(urldecode($params['city']));
            if ($geo) {
                $geo['city'] = urldecode($geo['city']);
                return $geo;
            }
        }
        $latitude  = $params['lat'] ?? null;
        $longitude = $params['lon'] ?? null;
        $city = $params['city'] ?? "Brasilia";

        if (!$latitude || !$longitude) {
            return [
                'lat'  => env('DEFAULT_LATITUDE', -15.7801),
                'lon'  => env('DEFAULT_LONGITUDE', -47.9292),
                'city' => $city
            ];
        }
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
            $timeStamp = strtotime($time);
            $diff = abs($timeStamp - $targetTimestamp);
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
        if (!isset($data['current_weather'])) {
            return null;
        }
        $current = $data['current_weather'];
        $humidity = null;
        if (isset($data['hourly']['time'], $data['hourly']['relativehumidity_2m'])) {
            $times = $data['hourly']['time'];
            $humidities = $data['hourly']['relativehumidity_2m'];
            $index = $this->findClosestTimeIndex($times, $current['time']);
            if ($index !== null) {
                $humidity = $humidities[$index];
            }
        }
        $mapping = $this->mapWeatherCode($current['weathercode']);
        $icon = $mapping['icon'];
        if (isset($current['is_day']) && $current['is_day'] == 0 && $icon === '☀️') {
            $icon = '🌙';
        }
        return [
            'city'        => $normalizedCity,
            'latitude'    => $lat,
            'longitude'   => $lon,
            'temperature' => $current['temperature'],
            'humidity'    => $humidity,
            'description' => $mapping['description'],
            'icon'        => $icon,
            'time'        => $current['time']
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
        if (!isset($data['daily'])) {
            return null;
        }
        $daily = $data['daily'];
        $forecast = [];
        $daysCount = count($daily['time']);
        for ($i = 0; $i < $daysCount; $i++) {
            $mapping = $this->mapWeatherCode($daily['weathercode'][$i]);
            $forecast[] = [
                'date'            => $daily['time'][$i],
                'temperature_max' => $daily['temperature_2m_max'][$i],
                'temperature_min' => $daily['temperature_2m_min'][$i],
                'description'     => $mapping['description'],
                'icon'            => $mapping['icon'],
                'sunrise'         => $daily['sunrise'][$i],
                'sunset'          => $daily['sunset'][$i]
            ];
        }
        return [
            'city' => $normalizedCity,
            'forecast' => $forecast
        ];
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
        if (!isset($data['daily'])) {
            return null;
        }
        $daily = $data['daily'];
        $avgTemp = ($daily['temperature_2m_max'][0] + $daily['temperature_2m_min'][0]) / 2;
        return $avgTemp . '°C';
    }

    public function convertTemperature(array $params)
    {
        $temperature = $params['temperature'] ?? null;
        $unit = strtoupper($params['unit'] ?? 'C');
        if (!is_numeric($temperature)) {
            return null;
        }
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
        return [
            'originalTemperature' => $temperature . '°C',
            'convertedTemperature' => $converted,
            'unit' => $unit
        ];
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
        if (!isset($data['daily'])) {
            return null;
        }
        $daily = $data['daily'];
        return [
            'sunrise' => $daily['sunrise'][0],
            'sunset'  => $daily['sunset'][0]
        ];
    }

    public function getRainForecast(array $params)
    {
        $forecastData = $this->get7DaysForecast($params);
        if (!$forecastData) {
            return null;
        }
        $forecast = $forecastData['forecast'];
        foreach ($forecast as $day) {
            if (in_array($day['description'], [
                'Light drizzle', 'Moderate drizzle', 'Dense drizzle',
                'Slight rain', 'Moderate rain', 'Heavy rain',
                'Slight rain showers', 'Moderate rain showers', 'Violent rain showers'
            ])) {
                return ['city' => $forecastData['city'], 'rainForecast' => 'Rain is forecasted in some days over the next 7 days.'];
            }
        }
        return ['city' => $forecastData['city'], 'rainForecast' => 'No rain is forecasted over the next 7 days.'];
    }

    public function compareTemperature(array $params)
    {
        $current = $this->getCurrentWeather($params);
        if (!$current) {
            return null;
        }
        $yesterdayAvg = str_replace('°C', '', $this->getYesterdayAverageTemp($params));
        $todayTemp = $current['temperature'];
        if ($todayTemp > $yesterdayAvg) {
            return [
                'city' => $current['city'],
                'todayTemperature' => $todayTemp . '°C',
                'yesterdayAverageTemperature' => $yesterdayAvg . '°C',
                'comparison' => "Today is warmer than yesterday."
            ];
        }
        if ($todayTemp < $yesterdayAvg) {
            return [
                'city' => $current['city'],
                'todayTemperature' => $todayTemp . '°C',
                'yesterdayAverageTemperature' => $yesterdayAvg . '°C',
                'comparison' => "Today is colder than yesterday."
            ];
        }
        return [
            'city' => $current['city'],
            'todayTemperature' => $todayTemp . '°C',
            'yesterdayAverageTemperature' => $yesterdayAvg . '°C',
            'comparison' => "Today's temperature is the same as yesterday."
        ];
    }

    public function getDailyPhrase(array $params)
    {
        $current = $this->getCurrentWeather($params);
        if (!$current) {
            return null;
        }
        $city = $current['city'];
        $phrase = "Today in " . $city . " is " . $current['description'] .
                  " with a temperature of " . $current['temperature'] . "°C";
        if ($current['humidity'] !== null) {
            return $phrase . " and humidity of " . $current['humidity'] . "%.";
        }
        return $phrase . ".";
    }
}
