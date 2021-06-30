<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PixxService;
use App\Services\PlentyMarketsService;

class MainController extends Controller
{
    private $headers = [
        'Access-Control-Allow-Origin'      => '*',
        'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Max-Age'           => '86400',
        'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With'
    ];

    public function execute(Request $request)
    {
        $imageIdArr = $request->json();
        if($request['token']!= env('CUSTOM_AUTH_TOKEN') || $request->ip() != env('PIXX_IP')){
            return response(['message' => 'Unauthorized'], 401, $this->headers);
        }

        PixxService::login();
        PlentyMarketsService::login();
        $imageData = [];

        //Filter out the images that have the correct status and are linked to a product, and save their data
        foreach ($imageIdArr as $imageId) {
            $data = PixxService::getImageData($imageId);
            $keywords = strtolower($data['keywords']);
            if ((strpos($keywords, 'pm-upload') !== false || strpos($keywords, 'pm_upload') !== false) && is_integer($data['dynamicMetadata']['Artikelnummer'])) {
                $imageData[$imageId] = $data;
            }
        }

        //Get the images encoded in base64 format
        foreach ($imageData as $imageId => &$data) {
            $data['encoded'] = PixxService::getEncodedImage($imageId);
        }

        //Upload images to PlentyMarkets and update Pixx keywords
        $productData = [];
        foreach ($imageData as $imageId => $data) {
            $success = PlentyMarketsService::uploadImage($data);
            if ($success) {
                if (!\key_exists($data['dynamicMetadata']['Artikelnummer'], $productData)) {
                    $productData[$data['dynamicMetadata']['Artikelnummer']] = PlentyMarketsService::getProductInfo($data['dynamicMetadata']['Artikelnummer']);
                }
                $temp = PixxService::updateImage($success, $imageId, $data, $productData[$data['dynamicMetadata']['Artikelnummer']]);
            } else {
                $temp = PixxService::updateImage($success, $imageId, $data);
            }
            if ($temp['status'] != 200) {
                abort(424, 'Failed on photo ' . $imageId . ' ' . $data['originalFilename'] . ' message: ' . $temp['help']);
            }
        }

        return response(['message' => 'success'], 201, $this->headers);
    }
}
