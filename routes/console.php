<?php

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('actions', function () {
    /**var \DefStudio\Telegraph\Models\TelegraphBot $bot*/
    $bot = TelegraphBot::find(1);


    $bot->registerCommands([
        'list' => 'Список заявок',
        'find' => 'Найти заявку',
        'add' => 'Создать заявку'
    ])->send();
});
