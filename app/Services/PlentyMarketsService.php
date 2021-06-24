<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PlentyMarketsService
{
    private static $token = null;
    private static $headers = [
        'Access-Control-Allow-Origin'      => '*', // This must be the formbeaver URL
        'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Max-Age'           => '86400',
        'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With'
    ];

    public static function login()
    {
        $request =  Http::post("https://isaak.plentymarkets-cloud01.com/rest/login", [
            'username' => env("PLENTYMARKETS_USERNAME"),
            'password' => env("PLENTYMARKETS_PASSWORD")
        ]);

        if ($request->failed()) {
            abort(401, $request->body(), self::$headers);
        }

        self::$token = $request['accessToken'];
    }

    public static function uploadImage($imageData)
    {
        $request = Http::withToken(self::$token)
            ->post("https://isaak.plentymarkets-cloud01.com/rest/items/{$imageData['dynamicMetadata']['Artikelnummer']}/images/upload", [
                'uploadFileName' => $imageData['originalFilename'],
                'uploadImageData' => $imageData['encoded'],
                'fileType' => strtolower($imageData['fileType']),
                'availabilities' => [
                    [
                        'type' => 'mandant',
                        'value' => -1
                    ]
                ]
            ]);
        return !$request->failed();
    }
}
