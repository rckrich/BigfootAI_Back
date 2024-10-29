<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function (){
    Route::post('/login', [ApiController::class, 'login']);

    Route::group(['middleware' => ['auth:sanctum']], function() {
        Route::post('/logout', [ApiController::class, 'logout']);
        Route::get('/openaikey', [ApiController::class, 'getOpenAiKey']);

        Route::group(['prefix' => 'threads'],
        function() {
            Route::post('/', [ApiController::class, 'createThread']);
            Route::put('/', [ApiController::class, 'updateThread']);
            Route::delete('/', [ApiController::class, 'deleteThread']);
            Route::get('//{limit}/{offset}', [ApiController::class, 'getThreadsByUser']);
        });
    });
});
