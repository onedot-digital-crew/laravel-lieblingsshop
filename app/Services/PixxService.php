<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PixxService
{
    private $token = null;

    public function __construct()
    {
        $request = Http::post(env("PIXX_URL") . "/accessToken", [
            'apiKey' => env("PIXX_API_KEY"),
            'refreshToken' => env("PIXX_REFRESH_TOKEN")
        ])->json();

        if ($request['status'] != 200) {
            abort($request['status'], $request['help']);
        }

        $this->token = $request['accessToken'];
    }

    public function getImageData($id)
    {
        return Http::get(env("PIXX_URL") . "/files/{$id}?accessToken={$this->token}" . '&options={"fields":["id","keywords","dynamicMetadata","originalFilename","fileType"]}')->json();
    }

    public function getEncodedImage($id)
    {
        return str_replace("\n", '', Http::get(env("PIXX_URL") . "/files/{$id}/convert?accessToken={$this->token}" . '&options={"responseType":"base64"}&downloadType=downloadFormat&downloadFormatId=' . env('PIXX_DOWNLOAD_FORMAT_ID'))->body());
    }

    public function updateImage($image, array $keywords, $error = null)
    {
        $options = [
            'keywords' => implode(',', $keywords),
        ];

        if ($error) {
            $options['dynamicMetadata'] = [
                'PlentymarketsError' => $error,
            ];
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => env("PIXX_URL") . "/files/{$image}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => 'accessToken='. $this->token . '&options=' . urlencode(json_encode($options)),
            CURLOPT_HTTPHEADER =>[
              'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        
        return json_decode($response, true);
    }
}
