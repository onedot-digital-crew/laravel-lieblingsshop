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

    public static function updateImage($success, $imageId, $imageData, $plentyResponse = null)
    {
        $token = self::$token;
        $keywords = explode(',', $imageData['keywords']);

        if ($success) {
            $keywords = array_merge($keywords, explode(', ', $plentyResponse->json()['texts'][0]['keywords']));
        }

        if (($key = array_search('pm-error', $keywords)) !== false) {
            unset($keywords[$key]);
            $keywords = array_values($keywords);
        }
        
        foreach ($keywords as &$keyword) {
            if (strtolower($keyword) == 'pm-upload' || strtolower($keyword) == 'pm_upload') {
                $keyword = $success ? 'pm-complete' : 'pm-error';
                $dynamicMetadata = $success ? "" : $plentyResponse->getReasonPhrase();
            }
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => env("PIXX_URL") . "/files/{$imageId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => 'accessToken='. $token . '&options=' . urlencode('{"keywords":"' . implode(',', $keywords) . '", "dynamicMetadata": {"PlentymarketsError": "' . $dynamicMetadata . '"}}'),
            CURLOPT_HTTPHEADER =>[
              'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        
        return json_decode($response, true);
    }
}
