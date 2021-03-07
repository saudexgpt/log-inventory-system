<?php

namespace App\Models\Invoice;

use App\Models\Logistics\Vehicle;
use App\Models\Transfers\TransferRequestWaybill;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispatchedTransferWaybill extends Model
{
    //
    use SoftDeletes;
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function waybill()
    {
        return $this->belongsTo(TransferRequestWaybill::class, 'transfer_request_waybill_id', 'id');
    }
}
