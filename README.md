Simple Google Maps Distance Calculator
=========================

Allows you to return distance and duration between 2 long/lat positions

Usage
--------

```php

use OwenMelbz\GoogleMapDistanceCalculator\DistanceCalculator;

$maps = new DistanceCalculator($apiKey = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXx');

$maps->setStartingPoint(52.629129, 1.290570);
$maps->setEndPoint(52.623990, 1.304594);

$maps->setTravelMode('walking'); // default 'driving'
$maps->setFormat('xml'); // default 'json'
$maps->setUnit('metric'); // default 'imperial'

$maps->getDistance();
$maps->getDistanceInMeters();

$maps->getTravelDuration();
$maps->getTravelDurationInSeconds();

$maps->calculate(); // returns whole response from google

```
