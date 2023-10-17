<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Telegram\Handler;
use Illuminate\Support\Facades\DB;

class MainController extends Controller
{
    public function index(){
        $handler = new Handler();

        $date = now()->format('Y-m-d');

        $products = Product::with('statuses')
            ->whereDate('created_at', $date)
            ->get();

        foreach ($products as $product) {
            $product->contractor = DB::table('bot_users')->where('user_chat_id', $product->contractor)->first()->name??null;
        }

//        $contractor = DB::table('bot_users')->where('user_chat_id', $product->contractor)->first();

        return view('welcome', compact('handler', 'products', 'date'));
    }

    public function indexByDate($date){
        // Преобразуйте дату в объект Carbon для более удобной работы
        $date = \Carbon\Carbon::parse($date)->format('Y-m-d');

        // Загрузите заявки, созданные в указанный день
        $products = Product::whereDate('created_at', $date)->get();
//        dd($products);

        return view('welcome', compact('products', 'date'));
    }

}
