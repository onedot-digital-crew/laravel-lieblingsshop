<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PlentyMarketsService
{
    private static $token = null;
    private static $headers = [
        'Access-Control-Allow-Origin'      => '*',
        'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Max-Age'           => '86400',
        'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With'
    ];

    public static function login()
    {
        $request =  Http::post(env("PLENTYMARKETS_URL") . "/login", [
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
            ->post(env("PLENTYMARKETS_URL") . "/items/{$imageData['dynamicMetadata']['Artikelnummer']}/images/upload", [
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

    public static function getProductInfo($productId)
    {
        return Http::withToken(self::$token)
            ->get(env("PLENTYMARKETS_URL") . "/items/$productId")->json();
    }
}
