<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PlentyMarketsService
{
    private $token = null;

    public function __construct()
    {
        $request =  Http::post(env("PLENTYMARKETS_URL") . "/login", [
            'username' => env("PLENTYMARKETS_USERNAME"),
            'password' => env("PLENTYMARKETS_PASSWORD")
        ]);

        if ($request->failed()) {
            abort(401, $request->body());
        }

        $this->token = $request['accessToken'];
    }

    public function uploadImage($id, $imageData, $position)
    {
        $request = Http::withToken($this->token)
            ->post(env("PLENTYMARKETS_URL") . "/items/" . $id . "/images/upload", [
                'uploadFileName' => $imageData['originalFilename'],
                'uploadImageData' => $imageData['encoded'],
                'position' => $position,
                'fileType' => strtolower($imageData['fileType']),
                'availabilities' => [
                    [
                        'type' => 'mandant',
                        'value' => -1
                    ]
                ]
            ]);

        return $request;
    }

    public function getProductInfo($productId)
    {
        return Http::withToken($this->token)
            ->get(env("PLENTYMARKETS_URL") . "/items/$productId");
    }
}
