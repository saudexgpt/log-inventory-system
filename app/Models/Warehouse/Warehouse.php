<?php

namespace App\Models\Warehouse;

use App\Customer;
use App\Laravue\Models\User;
use App\Models\Logistics\Vehicle;
use App\Models\Stock\Item;
use App\Models\Stock\ItemStockSubBatch;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    public function sites()
    {
        return $this->hasMany(Site::class);
    }
    // protected $hidden = ['pivot'];
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    public function itemStocks()
    {
        return $this->hasMany(ItemStockSubBatch::class);
    }
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
