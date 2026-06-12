<?php

namespace App\Services;

use GuzzleHttp\Client;
use Laravel\Forge\Forge;

class ForgeClientFactory
{
    public function make(string $token): Forge
    {
        $client = new Client([
            'base_uri' => 'https://forge.laravel.com/api/',
            'connect_timeout' => 5,
            'timeout' => 10,
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
                'User-Agent' => 'ForgeNative/1.0',
            ],
        ]);

        return (new Forge($token, $client))->setTimeout(10);
    }
}
