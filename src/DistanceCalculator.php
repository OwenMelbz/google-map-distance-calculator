<?php

namespace OwenMelbz\GoogleMapDistanceCalculator;

class DistanceCalculator
{
    protected $api;

    protected $apiKey;

    protected $unit = 'imperial';

    protected $format = 'json';

    protected $modeOfTransport = 'driving';

    protected $startLocation;

    protected $endLocation;

    protected $cachedResponse;

    public function __construct(string $apiKey = null)
    {
        $this->apiKey = $apiKey;
    }

    public function setUnit(string $unit)
    {
        $this->unit = $unit;

        return $this;
    }

    public function getUnit() : string
    {
        return $this->unit;
    }

    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat() : string
    {
        return $this->format;
    }

    public function setStartingPoint(float $lat, float $lon)
    {
        $this->startLocation = [
            'lat' => $lat,
            'lon' => $lon
        ];

        return $this;
    }

    public function getStartingPoint() : array
    {
        return $this->startLocation;
    }

    public function setEndPoint(float $lat, float $lon)
    {
        $this->endLocation = [
            'lat' => $lat,
            'lon' => $lon
        ];

        return $this;
    }

    public function setEndPoints(array $points)
    {
        $this->endLocation = $points;

        return $this;
    }

    public function getEndPoint() : array
    {
        return $this->endLocation;
    }

    public function setModeOfTransport(string $mode)
    {
        $this->modeOfTransport = $mode;

        return $this;
    }

    public function getDistance($key = 0) : string
    {
        if (!$this->cachedResponse) {
            $this->calculate();
        }

        $distance = $this->cachedResponse->rows[0]->elements[$key]->distance->text ?? '';

        return $distance;
    }

    public function getDistanceInMeters($key = 0) : float
    {
        if (!$this->cachedResponse) {
            $this->calculate();
        }

        $distance = $this->cachedResponse->rows[0]->elements[$key]->distance->value ?? 0;

        return (float) $distance;
    }

    public function getTravelDuration($key = 0) : string
    {
        if (!$this->cachedResponse) {
            $this->calculate();
        }

        $duration = $this->cachedResponse->rows[0]->elements[$key]->duration->text ?? '';

        return $duration;
    }

    public function getTravelDurationInSeconds($key = 0) : float
    {
        if (!$this->cachedResponse) {
            $this->calculate();
        }

        $duration = $this->cachedResponse->rows[0]->elements[$key]->duration->value ?? 0;

        return (float) $duration;
    }

    public function getStatus($key = 0) : string
    {
        if (!$this->cachedResponse) {
            $this->calculate();
        }

        return $this->cachedResponse->rows[0]->elements[$key]->status ?? 'UNKNOWN';
    }

    public function calculate() : object
    {
        $response = $this->makeRequest();

        $this->cachedResponse = $response;

        return $this->cachedResponse;
    }

    public function toArray()
    {
        if (!$this->cachedResponse) {
            $this->calculate();
        }
        
        $results = [];

        foreach ($this->cachedResponse->rows[0]->elements as $key => $unused) {
            $results[$key] = (object) [
                'distance' => $this->getDistance($key),
                'distance_meters' => $this->getDistanceInMeters($key),
                'duration' => $this->getTravelDuration($key),
                'duration_seconds' => $this->getTravelDurationInSeconds($key),
                'status' => $this->getStatus($key),
            ];
        }

        if (count($results) <= 1) {
            return current($results);
        }

        return $results;
    }

    protected function apiUrl() : string
    {
        return 'https://maps.googleapis.com/maps/api/distancematrix/';
    }

    private function makeRequest() : object
    {
        if (is_array(current($endpoints = $this->getEndPoint()))) {
            $points = array_map(function ($endpoint) {
                return implode(',', $endpoint);
            }, $endpoints);

            $desinations = implode('|', $points);
        } else {
            $desinations = implode(',', $this->getEndPoint());
        }

        $data = [
            'origins' => implode(',', $this->getStartingPoint()),
            'destinations' => $desinations,
            'units' => $this->getUnit(),
            'userIp' => $this->getUserIp(),
            'key' => $this->apiKey
        ];

        $data = array_filter($data);
        $queryStrings = http_build_query($data);

        $url = $this->apiUrl() . $this->getFormat() . '?' . $queryStrings;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }

    private function getUserIp() : string
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && isset($_SERVER['REMOTE_ADDR'])) {
            $client_ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return array_shift($client_ips);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && isset($_SERVER['REMOTE_ADDR'])) {
            $client_ips = explode(',', $_SERVER['HTTP_CLIENT_IP']);
            return array_shift($client_ips);
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '0.0.0.0';
    }
}
