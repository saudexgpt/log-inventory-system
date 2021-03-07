<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;

class ReturnedProductImage extends Model
{
    protected $fillable = ['uid', 'site_id', 'item_id', 'media_name', 'type', 'storage'];
    //
    public function returnedProduct()
    {
        return $this->hasMany(ReturnedProduct::class);
    }
}
