<?php
$router->group(['prefix' => 'api'], function () use ($router) {

    $router->get('weather/current', 'WeatherController@currentWeather');

    $router->get('weather/daily-phrase', 'WeatherController@dailyPhrase');

    $router->get('weather/forecast', 'WeatherController@forecast7Days');

    $router->get('weather/yesterday-average', 'WeatherController@yesterdayAverageTemp');

    $router->get('weather/convert', 'WeatherController@convertTemperature');

    $router->get('weather/sunrise-sunset', 'WeatherController@sunriseSunset');

    $router->get('weather/rain-forecast', 'WeatherController@rainForecast');

    $router->get('weather/compare', 'WeatherController@compareTemperature');
});
