<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PixxService;
use App\Services\PlentyMarketsService;

class ImageManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send all images with the matching tags to Lieblingsshop';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $pixxClient = new PixxService;
        $plentyClient = new PlentyMarketsService;

dd($pixxClient);

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

                    \Log::error($uploadResponse->getReasonPhrase(), $uploadResponse->json());
                } else {
                    $keywords->push($this->completeFlag);

                    // Add the Plentymarkets product's keywords to the image
                    $response = $plentyClient->getProductInfo($data['dynamicMetadata']['Artikelnummer']);
                    $keywords = $keywords->merge(explode(', ', $response->json()['texts'][0]['keywords']))->unique();

                    $pixxClient->updateImage($image, $keywords->toArray());
                }
            }
        }
    }
}
