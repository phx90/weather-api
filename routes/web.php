<?php
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('weather/current', 'WeatherDataController@currentWeather');
    $router->get('weather/daily-phrase', 'WeatherDataController@dailyPhrase');
    $router->get('weather/forecast', 'WeatherDataController@forecast7Days');
    $router->get('weather/yesterday-average', 'WeatherDataController@yesterdayAverageTemp');
    $router->get('weather/sunrise-sunset', 'WeatherDataController@sunriseSunset');
    $router->get('weather/rain-forecast', 'WeatherDataController@rainForecast');
    $router->get('weather/convert', 'WeatherUtilityController@convertTemperature');
    $router->get('weather/compare', 'WeatherUtilityController@compareTemperature');
});
