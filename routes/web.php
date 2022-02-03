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

// Route::post('/trigger', [
//     'uses' => 'MainController@execute'
// ]);

Route::post('/trigger', function (Request $request) {

    if ($request->ip() != env('PIXX_IP')) {
        return abort(401, 'Unauthorized');
    }

    if (!$request->events) {
        \Log::error('Pixx.io request without events object', $request->all());
        return abort(400);
    }

    $returnCode = Artisan::call('images:send');

    return response()->json([
        'message' => 'success',
    ]);
});