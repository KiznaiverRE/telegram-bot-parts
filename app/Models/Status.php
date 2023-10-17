<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $fillable = ['status', 'product_id'];
    public function product(){
        return $this->belongsTo(Product::class);
    }
}
