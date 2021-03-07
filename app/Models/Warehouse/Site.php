<?php

namespace App\Models\Warehouse;

use App\Laravue\Models\User;
use App\Models\Stock\SiteItemStock;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    public function itemStocks()
    {
        return $this->hasMany(SiteItemStock::class);
    }
}
