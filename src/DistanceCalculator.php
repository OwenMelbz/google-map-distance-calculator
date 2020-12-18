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
        $this->apiKey = null;
    }

    public function setUnit(string $unit) : void
    {
        $this->unit = $unit;
    }

    public function getUnit() : string
    {
        return $this->unit;
    }

    public function setFormat($format) : void
    {
        $this->format = $format;
    }

    public function getFormat() : string
    {
        return $this->format;
    }

    public function setStartingPoint(float $lat, float $lon) : void
    {
        $this->startLocation = [
            'lat' => $lat,
            'lon' => $lon
        ];
    }

    public function getStartingPoint() : array
    {
        return $this->startLocation;
    }

    public function setEndPoint(float $lat, float $lon) : void
    {
        $this->endLocation = [
            'lat' => $lat,
            'lon' => $lon
        ];
    }

    public function getEndPoint() : array
    {
        return $this->endLocation;
    }

    public function setModeOfTransport(string $mode) : void
    {
        $this->modeOfTransport = $mode;
    }

    public function getDistance() : string
    {
        if (!$this->cachedResponse) {
            $this->calculate();
        }

        $distance = $this->cachedResponse->rows[0]->elements[0]->distance->text ?? '';

        return $distance;
    }

    public function getDistanceInMeters() : float
    {
        if (!$this->cachedResponse) {
            $this->calculate();
        }

        $distance = $this->cachedResponse->rows[0]->elements[0]->distance->value ?? 0;

        return (float) $distance;
    }

    public function getTravelDuration() : string
    {
        if (!$this->cachedResponse) {
            $this->calculate();
        }

        $duration = $this->cachedResponse->rows[0]->elements[0]->duration->text ?? '';

        return $duration;
    }

    public function getTravelDurationInSeconds() : float
    {
        if (!$this->cachedResponse) {
            $this->calculate();
        }

        $duration = $this->cachedResponse->rows[0]->elements[0]->duration->value ?? 0;

        return (float)$duration;
    }

    public function calculate() : object
    {
        $response = $this->makeRequest();

        $this->cachedResponse = $response;

        return $this->cachedResponse;
    }

    protected function apiUrl() : string
    {
        return 'https://maps.googleapis.com/maps/api/distancematrix/';
    }

    private function makeRequest() : object
    {
        $data = [
            'origins' => implode(',', $this->getStartingPoint()),
            'destinations' => implode(',', $this->getEndPoint()),
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
