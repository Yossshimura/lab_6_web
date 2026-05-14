<?php

namespace App\Helpers;

use GuzzleHttp\Client;

class ClientFactory
{
    public static function make(string $baseUri): Client
    {
        return new Client([
            'base_uri' => $baseUri,
            'timeout'  => 5.0,
        ]);
    }
}