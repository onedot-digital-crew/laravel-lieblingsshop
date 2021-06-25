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
        $idArr = $request->json();

        PixxService::login();
        PlentyMarketsService::login();
        $imageData = [];

        //Filter out the images that have the correct status and are linked to a product, and save their data
        foreach ($idArr as $id) {
            $data = PixxService::getImageData($id);
            $keywords = strtolower($data['keywords']);
            if ((strpos($keywords, 'pm-upload') !== false || strpos($keywords, 'pm_upload') !== false) && is_numeric($data['dynamicMetadata']['Artikelnummer'])) {
                $imageData[$id] = $data;
            }
        }

        //Get the images encoded in base64 format
        foreach ($imageData as $id => &$data) {
            $data['encoded'] = PixxService::getEncodedImage($id);
        }

        //Upload images to PlentyMarkets
        foreach ($imageData as $id => $data) {
            $success = PlentyMarketsService::uploadImage($data);
            // if ($success) ... update Pixx data
        }

        return response(array_merge(['message' => 'success'], $response), 201, $this->headers);
    }
}
