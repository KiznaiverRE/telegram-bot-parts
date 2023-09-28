<?php

namespace App\Telegram;

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;

class Handler extends WebhookHandler
{
    public function actions(): void
    {
        Telegraph::message('Выберите действие')
            ->keyboard(
              Keyboard::make()->buttons([
                  Button::make('Перейть на сайт')->url('https://eaa.by'),
                  Button::make('Поставить лайк')->action('like'),
                  Button::make('Подписаться')
                      ->action('subscribe')
                      ->param('channel_name', '@eaa'),
              ])
            )->send();
    }

    public function like(): void{
        $chat = \DefStudio\Telegraph\Models\TelegraphChat::where('chat_id', -4033956691)->first();

        $chat->message('Лайк из экшена лайк')->send();
//        Telegraph::message('Спасибо за лайк')->send();
    }

    public function subscribe(): void{
        $this->reply("Спасибо за подписку на канал {$this->data->get('channel_name')}");
    }

    public function list(): void{
        $this->reply('Список товаров');
    }

    public function find(Stringable $text): void{

    }

    protected function handleUnknownCommand(Stringable $text): void
    {
        if ($text->value() === '/start') {
            $this->reply("Привет. Давай начнём пользоваться ботом $text");
        }else{
            $this->reply('Я не знаю такой команды');
        }
    }

    //Можно обрабатывать любое сообщение, которое присылает пользователь
//    protected function handleChatMessage(Stringable $text): void
//    {
//        $this->reply($text);
//    }

    //Дебажим приложение
    protected function handleChatMessage(Stringable $text): void
    {
        // записываем и смотрим в laravel.log
        Log::info(json_encode($this->message->toArray(), JSON_UNESCAPED_UNICODE));

        // получаем файл
//        $this->message->document();

        // Получаем доступ к модели бота
//        $this->bot;
        $this->reply(false);
    }
}
