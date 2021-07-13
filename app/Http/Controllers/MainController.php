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

    // Tags that will trigger the image upload
    private $acceptedUploadTriggers = [
        'pmupload',
        'pm-upload',
        'pm_upload',
    ];

    private $errorFlag = 'pm-error';

    private $completeFlag = 'pm-complete';

    public function execute(Request $request)
    {
        if (!($request['token'] == env('CUSTOM_AUTH_TOKEN') || $request->ip() == env('PIXX_IP'))) {
            return response(['message' => 'Unauthorized'], 401, $this->headers);
        }

        $pixxClient = new PixxService;
        $plentyClient = new PlentyMarketsService;

        foreach ($request->json() as $index => $image) {
            $data = $pixxClient->getImageData($image);

            if ($this->hasUploadKeyword($data) && $this->hasArticleNumber($data)) {
                $data['encoded'] = $pixxClient->getEncodedImage($image);

                $uploadResponse = $plentyClient->uploadImage($data, $index);

                // Gather the keywords in a collection
                $keywords = $this->prepareKeywords($data);

                if ($uploadResponse->failed()) {
                    $keywords->push($this->errorFlag);
                    $pixxClient->updateImage($image, $keywords->toArray(), $uploadResponse->getReasonPhrase());
                } else {
                    $keywords->push($this->completeFlag);

                    // Add the Plentymarkets product's keywords to the image
                    $response = $plentyClient->getProductInfo($data['dynamicMetadata']['Artikelnummer']);
                    $keywords = $keywords->merge(explode(', ', $response->json()['texts'][0]['keywords']))->unique();

                    $pixxClient->updateImage($image, $keywords->toArray());
                }
            }
        }

        return response(['message' => 'success'], 201, $this->headers);
    }

    private function hasUploadKeyword($data)
    {
        $keywords = explode(',', strtolower($data['keywords']));

        // If there are keywords in common with the upload triggers
        return count(array_intersect($keywords, $this->acceptedUploadTriggers)) > 0;
    }

    private function hasArticleNumber($data)
    {
        return is_numeric($data['dynamicMetadata']['Artikelnummer']);
    }

    private function prepareKeywords($data)
    {
        $results = collect(explode(',', $data['keywords']));

        /**
         * Remove the upload trigger ("pm-upload" or similar) and the
         * "pm-error" flag before trying again
         */
        $results = $results->filter(function ($i) {
            return !in_array($i, $this->acceptedUploadTriggers) && $i != $this->errorFlag;;
        });

        return $results;
    }
}
