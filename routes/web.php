<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/trigger', function (Request $request) {

    if (!$request->json('events')) {
        \Log::error('Pixx.io request without events object', $request->all());
        return abort(400, 'Pixx.io request without events object');
    }


    $pixxClient = new \App\Services\PixxService;
    $plentyClient = new \App\Services\PlentyMarketsService;

    // Flatten the events array from the request into a singular array of image ids
    $images = collect($request->json('events'))->pluck('id')->toArray();


    $returnCode = Artisan::call('images:send', [
        'image' => $images
    ]);

    return response()->json([
        'message' => 'success',
    ]);
})->middleware('pixx');