<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PixxService
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
        $request = Http::post(env("PIXX_URL") . "/accessToken", [
            'apiKey' => env("PIXX_API_KEY"),
            'refreshToken' => env("PIXX_REFRESH_TOKEN")
        ])->json();

        if ($request['status'] != 200) {
            abort($request['status'], $request['help'], self::$headers);
        }

        self::$token = $request['accessToken'];
    }

    public static function getImageData($id)
    {
        $token = self::$token;
        return Http::get(env("PIXX_URL") . "/files/{$id}?accessToken={$token}" . '&options={"fields":["id","keywords","dynamicMetadata","originalFilename","fileType"]}')->json();
    }

    public static function getEncodedImage($id)
    {
        $token = self::$token;
        return str_replace("\n", '', Http::get(env("PIXX_URL") . "/files/{$id}/convert?accessToken={$token}" . '&options={"responseType":"base64"}')->body());
    }
}
