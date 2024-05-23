<?php
/**
 * Replacement weather service for `info.gigaset.net`
 *
 * @copyright Copyright (c) 2024 Tilman Vogel <tilman.vogel@web.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Retrieves the weather description from a weather object.
 *
 * @param object $weather The weather object.
 * @return string The weather description.
 */
function weather_type($weather)
{
  return $weather->description;
};

/**
 * Summarizes the most frequent weather types.
 *
 * @param array $weather_types The array of weather types.
 * @return array The array of most frequent weather types.
 *
 * Limits to a maximum of 2 weather types.
 */
function summarize_weather_types($weather_types)
{
  $weather_types = array_count_values($weather_types);
  arsort($weather_types);
  $weather_types = array_slice($weather_types, 0, 2);
  return array_keys($weather_types);
}

/**
 * Post-processes the weather types array.
 *
 * @param array $weather_types The array of weather types.
 * @return array The post-processed weather types array.
 *
 * This function transforms to abbreviations when suitable.
 */
function postprocess_weather_types($weather_types)
{
  $result = array();
  $tight = count($weather_types) > 1;
  foreach ($weather_types as $weather_type) {
    $weather_type = preg_replace('/([Üü]berw)iegend/', '$1.', $weather_type);
    if ($tight) {
      $weather_type = preg_replace('/(bew)ölkt/i', '$1.', $weather_type);
      $weather_type = preg_replace('/(bed)eckt/i', '$1.', $weather_type);
    }
    $result[] = $weather_type;
  }
  return $result;
}

/**
 * Returns the weekday abbreviation for the given day of the week.
 *
 * @param int $dow The day of the week (0-6, where 0 represents Sunday).
 * @return string The weekday abbreviation.
 */
function weekday($dow)
{
  static $weekdays = array('So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa');
  return $weekdays[$dow];
}

/**
 * Retrieves weather forecast data from the OpenWeatherMap API.
 *
 * @param float $lat The latitude of the location.
 * @param float $lon The longitude of the location.
 * @param string $api_key The API key for accessing the OpenWeatherMap API.
 * @return object The weather forecast data.
 * @throws Exception If there is an error retrieving the data.
 */
function retrieve_weather($lat, $lon, $api_key)
{
  $url = "http://api.openweathermap.org/data/2.5/forecast?lat=$lat&lon=$lon&appid=$api_key&units=metric&lang=de";
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec($curl);
  if ($result === false) {
    throw new Exception("Fehler beim Abrufen der Daten: " . curl_error($curl));
  }
  $result = json_decode($result);

  if ($result->cod != 200) {
    throw new Exception("Fehler beim Abrufen der Daten: " . $result->message);
  }
  return $result;
}

/**
 * Aggregates the daily weather data from the forecast.
 *
 * @param object $forecast The weather forecast data.
 * @return array The aggregated daily weather data.
 */
function aggregate_daily_weather($forecast)
{
  $weatherData = array();

  // Iterate over each item in the "list" array
  foreach ($forecast->list as $item) {
    if ($item->sys->pod == "n")
      continue;

    $date = date('d.m.Y', $item->dt);
    $dow = date('w', $item->dt);
    $date = weekday($dow) . ", " . $date;

    // Check if the date already exists in the nested array
    if (!isset($weatherData[$date])) {
      // If not, initialize the nested array for the date
      $weatherData[$date] = array(
        'min_temp' => $item->main->temp_min,
        'max_temp' => $item->main->temp_max,
        'total_rain' => $item->rain->{"3h"},
        'weather_types' => array_map('weather_type', $item->weather)
      );
    } else {
      // If the date already exists, update the minimum and maximum temperature
      $weatherData[$date]['min_temp'] = min($weatherData[$date]['min_temp'], $item->main->temp_min);
      $weatherData[$date]['max_temp'] = max($weatherData[$date]['max_temp'], $item->main->temp_max);
      // Update the total amount of rain
      $weatherData[$date]['total_rain'] += $item->rain->{"3h"};
      // Add the weather type to the array
      $weatherData[$date]['weather_types'] = array_merge(
        $weatherData[$date]['weather_types'],
        array_map('weather_type', $item->weather)
      );
    }
  }

  // Summarize the weather types for each date
  foreach ($weatherData as $date => $data) {
    $weatherData[$date]['weather_types']
      = postprocess_weather_types(
        summarize_weather_types(
          $data['weather_types']
        )
      );
  }
  return $weatherData;
}
