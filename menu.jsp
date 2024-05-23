<?php echo '<?xml version="1.0" encoding="utf-8"?>' ?>

<!DOCTYPE html PUBLIC "-//OPENWAVE//DTD XHTML Mobile 1.0//EN" "http://www.openwave.com/dtd/xhtml-mobile10.dtd">
<html>

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

require('weather.php');

// Get configuration from environment variables
$lat = getenv('LATITUDE');
$lon = getenv('LONGITUDE');
$city = getenv('CITY');
$api_key = getenv('OPENWEATHERMAP_API_KEY');
?>

<head>
  <title><?php echo $city; ?></title>
</head>

<body>
  <?php
  try {
    $result = retrieve_weather($lat, $lon, $api_key);
    $weatherData = aggregate_daily_weather($result);

    // Display the weather data
    foreach ($weatherData as $date => $data) {
      $minTemp = $data['min_temp'];
      $maxTemp = $data['max_temp'];
      $totalRain = $data['total_rain'];
      $weatherTypes = $data['weather_types'];

      echo "<p style='text-align:center'>$date<br/>";
      echo sprintf('%.1f/%.1f°C/%.0f mm', $minTemp, $maxTemp, $totalRain), "<br/>";
      echo implode('/', $weatherTypes) . "</p>";
    }
  } catch (Exception $e) {
    echo "<p style='text-align:center'><b>Error:</b> " . $e->getMessage() . "</p>";
  }
  ?>
</body>
</html>
