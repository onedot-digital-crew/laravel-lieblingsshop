<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PixxService
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
        $request = Http::post("https://lieblingsshop.pixxio.media/cgi-bin/api/pixxio-api.pl/json/accessToken", [
            'apiKey' => env("API_KEY"),
            'refreshToken' => env("REFRESH_TOKEN")
        ])->json();

        if ($request['status'] != 200) {
            abort($request['status'], $request['help'], self::$headers);
        }

        self::$token = $request['accessToken'];
    }

    public static function getImageData($id)
    {
        $token = self::$token;
        return Http::get("https://lieblingsshop.pixxio.media/cgi-bin/api/pixxio-api.pl/json/files/{$id}?accessToken={$token}" . '&options={"fields":["id","keywords","dynamicMetadata","originalFilename","fileType"]}')->json();
    }

    public static function getEncodedImage($id)
    {
        $token = self::$token;
        return str_replace("\n", '', Http::get("https://lieblingsshop.pixxio.media/cgi-bin/api/pixxio-api.pl/json/files/{$id}/convert?accessToken={$token}" . '&options={"responseType":"base64"}')->body());
    }
}
