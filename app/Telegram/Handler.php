<?php

namespace App\Telegram;

use App\Models\Product;
use App\Models\Status;
use App\Models\User;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Stringable;

class Handler extends WebhookHandler
{
    protected $questions = ['name', 'comment'];

    protected function handleUnknownCommand(Stringable $text): void
    {
        $this->reply('Неизвестная команда');
    }
    public function start(): void{
        $this->reply('Выберите пункт из меню ниже');
    }
    public function list(): void
    {
        $products = Product::all();
        $buttons = [];

        foreach ($products as $product) {
            $buttons[] = Button::make($product->name)->action('pick')->param('productId', $product->id);
        }

        Telegraph::chat($this->chat)->message('Выбери заявку')
            ->keyboard(
                Keyboard::make()->buttons($buttons))->send();
    }

    public function pick(){
        $productId = $this->data->get('productId');
        $product = Product::with('statuses')->find($productId);
        $statuses = '';

        foreach ($product->statuses as $status) {
            $statuses .= '('. $status->created_at . ') ' . $status->status . "\n";
        }

        $user = DB::table('bot_users')->where('user_chat_id', $product->contractor)->first();
        $author = DB::table('bot_users')->where('user_chat_id', $product->creator_id)->first();

//        Log::info($author);

        $contractor = isset($user->name) ? "<strong>Исполнитель: </strong> $user->name\n\n" : "<strong>Исполнитель: </strong>Нет исполнителя\n\n";

        $message = "<strong>Дата заявки</strong>: $product->created_at\n\n".
            "<strong>Создатель заявки: </strong> $author->name\n\n".
            "<strong>Наименование</strong>: $product->name\n\n".
            "<strong>Комментарий инициатора заявки:</strong> $product->comment\n\n".
            $contractor.
            "<strong>Примечания: </strong>\n\n".
            "$statuses"
        ;

        Telegraph::chat($this->chat)->html($message)->send();
        $this->chooseAction($productId);
    }

    public function chooseAction(){
        $productId = $this->data->get('productId');
        $product = Product::find($productId);
        $buttons = [];
        if ($product->contractor == null){
            $buttons[] = Button::make('Принять заявку')->action('acceptApplication')->param('productId', $productId);
        }
        if ($product->contractor == $this->chat->chat_id){
            $buttons[] = Button::make('Добавить примечание')->action('addNote')->param('productId', $productId);
            $buttons[] = Button::make('Удалить одно из примечаний')->action('notesList')->param('productId', $productId);
        }
        if ($this->chat->chat_id == $product->creator_id){
            $buttons[] = Button::make('Удалить заявку')->action('deleteApplication')->param('productId', $productId);
            $buttons[] = Button::make('Написать итоговый комментарий к заявке')->action('addResult')->param('productId', $productId);
        }

        Telegraph::chat($this->chat)->message('Выберите действие')->keyboard(
            Keyboard::make()->buttons($buttons)
        )->send();
    }

    public function deleteApplication(){
        $productId = $this->data->get('productId');
        $product = Product::find($productId);
        $product->statuses()->delete();
        $product->delete();
        $this->reply('Заявка удалена.');
    }

    public function addResult(){
        $productId = $this->data->get('productId');
        Cache::put('user_' . $this->chat->chat_id . '_state', 'response', 60);
        Cache::put('user_' . $this->chat->chat_id . '_mode', 'addResult', 60);
        Cache::put('user_' . $this->chat->chat_id . '_product_id', $productId, 60);
        Telegraph::chat($this->chat)->message('Напишите текст итогового комментария')->send();
    }
    public function saveResult($text){
        $productId = Cache::get('user_' . $this->chat->chat_id . '_product_id');
        $product = Product::find($productId);
        $product->result = $text;
        $product->save();
        $this->reply('Итоговый комментарий добавлен.');
    }

    public function acceptApplication(){
        $productId = $this->data->get('productId');
        $product = Product::find($productId);
        if (!$product->contractor){
            $product->contractor = $this->chat->chat_id;
            $product->save();
            Telegraph::chat($this->chat)->message('Вы успешно приняли заявку')->send();
        }else{
            Telegraph::chat($this->chat)->message("Заявка уже принята пользователем {$this->chat->name}")->send();
        }
//        $this->handleChatMessage();
    }

    public function addNote(){
        $productId = $this->data->get('productId');
        Cache::put('user_' . $this->chat->chat_id . '_state', 'response', 60);
        Cache::put('user_' . $this->chat->chat_id . '_mode', 'addNote', 60);
        Cache::put('user_' . $this->chat->chat_id . '_product_id', $productId, 60);
        Telegraph::chat($this->chat)->message('Напишите текст заметки')->send();
    }

    public function saveNote($text){
        $productId = Cache::get('user_' . $this->chat->chat_id . '_product_id');
        $status = new Status();
        $status->status = $text;
        $status->product_id = $productId;
        $status->save();
        $this->reply('Заметка добавлена.');
    }

    public function notesList(){
        $productId = $this->data->get('productId');
        $statuses = Status::where('product_id', $productId)->get();
        $buttons = [];
        foreach ($statuses as $status) {
            $buttons[] = Button::make($status->status)->action('removeNote')->param('statusId', $status->id);
        }

        Telegraph::chat($this->chat)->message('Выберите примечание, которое хотите удалить')->keyboard(
            Keyboard::make()->buttons($buttons)
        )->send();
    }

    public function removeNote(){
        $statusId = $this->data->get('statusId');
        $status = Status::where('id', $statusId)->first();
        $status->delete();
        $this->reply('Примечание удалено.');
    }



    public function find(){
        Cache::put('user_' . $this->chat->chat_id . '_state', 'response', 60);
        Cache::put('user_' . $this->chat->chat_id . '_mode', 'search', 60);
        Telegraph::chat($this->chat)->message('Введите название товара или его часть')->send();
    }

    protected function handleChatMessage(Stringable $text): void
    {
        $state = Cache::get('user_' . $this->chat->chat_id . '_state');
        if ($state !== 'response') {
            $this->reply('Выберите пункт из меню');
            return;
        }else{
            $mode = Cache::get('user_' . $this->chat->chat_id . '_mode');
            if ($mode === 'search') {
                $this->returnSearchingProduct($text);
            }
            if ($mode === 'add'){
                $this->addProduct($text);
            }
            if ($mode === 'addNote'){
                $this->saveNote($text);
            }
            if ($mode === 'addResult'){
                $this->saveResult($text);
            }
        }
    }

    public function returnSearchingProduct($text){
        $products = Product::where('name', 'like', "%$text%")->get();
        foreach ($products as $product) {
            $buttons[] = Button::make($product->name)->action('pick')->param('productId', $product->id);
        }

        Telegraph::chat($this->chat)->message('Выбери заявку')
            ->keyboard(
                Keyboard::make()->buttons($buttons))->send();
        Cache::clear();
    }

    public function add(){
        Cache::put('user_' . $this->chat->chat_id . '_state', 'response', 60);
        Cache::put('user_' . $this->chat->chat_id . '_mode', 'add', 60);
        $this->reply('Введите название запчасти');
    }

    public function addProduct($text){
        $answers = Cache::get('user_' . $this->chat->chat_id . '_answers')??null;
        $chats = TelegraphChat::all();

        if (!$answers){
            $answers['name'] = $text;
            Cache::put('user_' . $this->chat->chat_id . '_answers', $answers, 60);
            $this->reply('Теперь напишите комментарий');
        }else{
            $answers['comment'] = $text;
            // Здесь выполните создание нового продукта и сохранение его в базу данных
            $product = new Product();
            $product->name = $answers['name'];
            $product->comment = $answers['comment'];
            $product->creator_id = $this->chat->chat_id;
            $product->save();

            Cache::clear();
            foreach ($chats as $chat){
                Telegraph::chat($chat)->message('Добавлена новая заявка!')->keyboard(
                    Keyboard::make()->buttons([
                        Button::make('Список заявок')->action('list')
                    ])
                )->send();
            }
        }
    }
}
