<?php

Route::group(['middleware' => ['web', 'admin']], function () {

    Route::get('/admin/ui', 'Gametech\Ui\Http\Controllers\Admin\UiController@index')->defaults('_config', [
        'view' => 'ui::admin.index',
    ])->name('ui.admin.index');

});