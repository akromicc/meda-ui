
    // === RUTAS DE DISPOSITIVOS WHATSAPP ===
    Route::prefix('devices')->group(function () {
        Route::get('/', [DeviceController::class, 'index']);
        Route::get('/metadata', [DeviceController::class, 'tableMetadata']);
        Route::post('/', [DeviceController::class, 'store']);
        Route::get('/{device}', [DeviceController::class, 'show']);
        Route::put('/{device}', [DeviceController::class, 'update']);
        Route::delete('/{device}', [DeviceController::class, 'destroy']);
        Route::get('/search/users', [DeviceController::class, 'searchUsers']);
        
        Route::prefix('{device}')->group(function () {
            Route::get('/qr', [DeviceController::class, 'getQrCode']);
            Route::post('/connect', [DeviceController::class, 'connect']);
            Route::post('/disconnect', [DeviceController::class, 'disconnect']);
            Route::get('/stats', [DeviceController::class, 'stats']);
        });
    });
