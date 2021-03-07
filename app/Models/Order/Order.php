<?php

namespace App\Models\Order;

use App\Models\Invoice\Invoice;
use App\Models\Setting\Currency;
use App\Models\Warehouse\Site;
use App\Models\Warehouse\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    //
    use SoftDeletes;
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
    public function site()
    {
        return $this->belongsTo(Site::class);
    }
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
    public function histories()
    {
        return $this->hasMany(OrderHistory::class);
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
