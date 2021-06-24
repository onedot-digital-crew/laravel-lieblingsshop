<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PixxService;
use App\Services\PlentyMarketsService;

class MainController extends Controller
{
    private $headers = [
        'Access-Control-Allow-Origin'      => '*', // This must be the formbeaver URL
        'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Max-Age'           => '86400',
        'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With'
    ];

    public function execute(Request $request)
    {
        $idArr = $request->json();

        PixxService::login();
        // PlentyMarketsService::login();
        $imageData = [];

        //Filter out the images that have the correct status and get their target Product Id
        foreach ($idArr as $id) {
            $data = PixxService::getImageData($id);
            dd($data);
            $keywords = strtolower($data['keywords']);
            if ((strpos($keywords, 'pm-upload') !== false || strpos($keywords, 'pm_upload') !== false) && is_numeric($data['dynamicMetadata']['Artikelnummer'])) {
                $imageData[$id] = $data['dynamicMetadata']['Artikelnummer'];
            }
        }

        //Get the image encoded in base 64 format
        foreach ($imageData as $id => &$data) {
            $data['encoded'] = PixxService::getEncodedImage($id);
        }

        //Upload Images to PlentyMarkets
        foreach ($imageData as $id => $data) {
            PlentyMarketsService::uploadImage($data);
        }
        return response(array_merge(['message' => 'success'], $response), 201, $this->headers);
    }
}
