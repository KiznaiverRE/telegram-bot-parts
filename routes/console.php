<?php

use DefStudio\Telegraph\Facades\Telegraph;
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


Artisan::command('tester', function () {
    //Получить бота
    /** @var \DefStudio\Telegraph\Models\TelegraphBot $bot */
    $bot = \DefStudio\Telegraph\Models\TelegraphBot::find(1);




    //Регистрация команд
    $bot->registerCommands([
        'list' => 'Получить список запчастей',
        'find' => 'Найти запчасть',
    ])->send();
});

Artisan::command('addButtonsToChat', function () {
    $chat = \DefStudio\Telegraph\Models\TelegraphChat::where('chat_id', -4033956691)->get();
});

Artisan::command('createChat', function () {
    //Получить бота
    /** @var \DefStudio\Telegraph\Models\TelegraphBot $bot */
    $bot = \DefStudio\Telegraph\Models\TelegraphBot::find(1);


    $chat = $bot->chats()->create([
        'chat_id' => -4033956691,
        'name' => 'zapchasti_EAA_bot',
    ]);
});
