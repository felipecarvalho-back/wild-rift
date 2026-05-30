<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'dashboard')->name('dashboard');
Route::livewire('/series/create', 'create-series')->name('series.create');
Route::livewire('/champions', 'champion-manager')->name('champions.index');
Route::livewire('/series/{series}/draft', 'draft-simulator')->name('series.draft');
Route::livewire('/series/{series}/summary', 'series-summary')->name('series.summary');