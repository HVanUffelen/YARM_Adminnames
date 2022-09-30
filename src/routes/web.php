<?php
Route::group(['namespace'=>'Yarm\Adminnames\Http\Controllers','prefix'=>'dlbt','middleware'=>['web']], function (){

    Route::get('/allRefNames', 'AdminnamesController@getRefNames')
        ->name('get_ref_names');
    Route::get('/allUncheckedRefNames', 'AdminnamesController@getUncheckedRefNames')
        ->name('get_unchecked_ref_names');
    Route::get('/dataCleaningManual', 'AdminnamesDataCleaningController@manual')
        ->name('dataCleaning_manual');
    Route::get('/addDataList', 'AdminnamesDataCleaningController@addDataList')
        ->name('wikidata_list');
    Route::post('/addDataConfirm', 'AdminnamesDataCleaningController@getData')
        ->name('get_wikidata');

});
