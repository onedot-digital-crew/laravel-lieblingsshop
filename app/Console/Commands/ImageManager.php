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
    protected $signature = 'images:send {image*}';

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

        $images = $this->argument('image');

        $this->info('Sending ' . count($images) . ' images to Plentymarkets');

        foreach ($images as $index => $id) {
            $this->line('Grabbing meta data for image ' . $id);

            $data = $pixxClient->getImageData($id);

            // Sometimes the event doesn't contain an article number...
            $articleNumber = $this->extractArticleNumber($data);

            if ($articleNumber && $this->containsUploadKeyword($data['keywords'])) {
                $data['encoded'] = $pixxClient->getEncodedImage($id);

                echo 'Sending image to Plentymarkets...';
                $uploadResponse = $plentyClient->uploadImage($articleNumber, $data, $index);
                $this->line('done.');

                // Gather the keywords in a collection
                $keywords = $this->prepareKeywords($data['keywords']);

                if ($uploadResponse->failed()) {
                    $keywords->push(config('app.error_flag'));
                    $this->line('Writing keyword info back to Pixx.io');
                    $pixxClient->updateImage($id, $keywords->toArray(), $uploadResponse->getReasonPhrase());

                    \Log::error($uploadResponse->getReasonPhrase(), $uploadResponse->json());
                    $this->error($uploadResponse->getReasonPhrase());
                } else {
                    $keywords->push(config('complete_flag'));

                    // Add the Plentymarkets product's keywords to the image
                    $response = $plentyClient->getProductInfo($data['dynamicMetadata']['Artikelnummer']);
                    $keywords = $keywords->merge(explode(', ', $response->json()['texts'][0]['keywords']))->unique();

                    $pixxClient->updateImage($id, $keywords->toArray());
                }
            } else {
                $this->comment('The image has no upload keyword. Ignoring.');
            }
        }
    }

    /**
     * Given a Pixx.io response array, attempts to return the associated
     * article number or returns null when none was detected.
     *
     * @param $data array from a pixx.io response
     * @return int or null
     */
    private function extractArticleNumber($data)
    {
        if (is_numeric($data['dynamicMetadata']['Artikelnummer'])) {
            return $data['dynamicMetadata']['Artikelnummer'];
        } else {
            $articleNumbers = explode(PHP_EOL, $data['dynamicMetadata']['Artikelnummer']);

            /**
             * INFO: If the client requests muliple article number support,
             * that be done by extending the logic below.
             */
            if (count($articleNumbers)) {
                return array_shift($articleNumbers);
            }
        }

        return null;
    }

    /**
     * Removes the upload trigger and places uploaded keyword in the image
     * data's keywords and returns the result in a laravel collection.
     *
     * @param $keywords string containing the keywords to replace
     * @return Illuminate\Support\Collection
     */
    private function prepareKeywords($keywords)
    {
        $results = collect(explode(',', $keywords));

        /**
         * Remove the upload trigger ("pm-upload" or similar) and the
         * "pm-error" flag before trying again
         */
        $results = $results->filter(function ($i) {
            return !in_array($i, config('app.accepted_upload_triggers')) && $i != config('app.error_flag');
        });

        return $results;
    }

    /**
     * Checks whether the given string contians one of the accepted upload
     * keywords defined in the "accepted_upload_triggers" in app config
     *
     * @param $keywords string containing the keywords to check
     * @return boolean
     */
    private function containsUploadKeyword($keywords)
    {
        $keywords = explode(',', strtolower($keywords));

        // If there are keywords in common with the upload triggers
        return count(array_intersect($keywords, config('app.accepted_upload_triggers'))) > 0;
    }
}
