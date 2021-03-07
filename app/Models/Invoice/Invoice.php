<?php

namespace App\Models\Invoice;

use App\Customer;
use App\Laravue\Models\User;
use App\Models\Order\Order;
use App\Models\Warehouse\Site;
use App\Models\Warehouse\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    //
    use SoftDeletes;
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function customer()
    {
        return $this->belongsTo(Site::class, 'customer_id', 'id');
    }
    public function histories()
    {
        return $this->hasMany(InvoiceHistory::class);
    }
    public function waybillItems()
    {
        return $this->hasMany(WaybillItem::class);
    }
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
    public function waybills()
    {
        return $this->belongsToMany(Waybill::class);
    }
    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by', 'id');
    }
}
