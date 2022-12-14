<?php
Route::group(['namespace'=>'Yarm\Adminnames\Http\Controllers','prefix'=> strtolower(config('yarm.sys_name')),'middleware'=>['web']], function (){

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
    Route::post('/confirmWikiData', 'AdminnamesDataCleaningController@confirmData')
        ->name('confirm_wikidata');
    Route::post('/confirmOneWikiData', 'AdminnamesDataCleaningController@confirmOne')
        ->name('confirm_one_wikidata');
    Route::get('/dataCleaningList', 'AdminnamesDataCleaningController@list')
        ->name('dataCleaning_list');
    Route::post('/dataCleaningCheck', 'AdminnamesDataCleaningController@setChecked')
        ->name('dataCleaning_check');
    Route::post('/dataCleaning', 'AdminnamesDataCleaningController@clean')
        ->name('dataCleaning_clean');

});
