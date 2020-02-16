<?php

$endpoints = [
  'except' => ['create', 'edit','destroy','head']
];


Route::match(['options'], 'api/1.0/authentication', 'Pursuit\Authentication\app\Controllers\Authentication@options');
Route::match(['put'], 'api/1.0/authentication', 'Pursuit\Authentication\app\Controllers\Authentication@authenticate');
Route::delete('api/1.0/authentication', 'Pursuit\Authentication\app\Controllers\Authentication@destroy');
Route::resource('api/1.0/authentication', 'Pursuit\Authentication\app\Controllers\Authentication', $endpoints);
