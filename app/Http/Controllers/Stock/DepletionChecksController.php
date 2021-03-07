<?php

namespace App\Http\Controllers\Stock;

use App\Driver;
use App\Http\Controllers\Controller;
use App\Http\Traits\GoodsTransferTrait;
use App\Models\Stock\ItemStockSubBatch;
use App\Models\Warehouse\Warehouse;
use Illuminate\Http\Request;

class DepletionChecksController extends Controller
{
    use GoodsTransferTrait;
    use GoodsTransferTrait;
    public function checkForProductDepletion(Request $request)
    {
        $warehouses = Warehouse::where('id', '!=', 3)->get(); // we don't want virtual warehouse
        $supplying_warehouse = Warehouse::where('is_main', 1)->first();
        $depletions = [];
        foreach ($warehouses as $warehouse) {
            // $minimum_quantity_threshold = $item->minimum_quantity_threshold;

            $items_in_stock = ItemStockSubBatch::with(['item', 'warehouse'])->groupBy(['item_id'])->where('warehouse_id', $warehouse->id)->select('*', \DB::raw('SUM(quantity) as quantity'), \DB::raw('SUM(in_transit) as in_transit'), \DB::raw('SUM(supplied) as supplied'), \DB::raw('SUM(balance) as balance'), \DB::raw('SUM(reserved_for_supply) as reserved_for_supply'))->get();
            if ($items_in_stock->isNotEmpty()) {
                # code...

                $request_items = [];
                foreach ($items_in_stock as $item_in_stock) {
                    $minimum_quantity_threshold = $item_in_stock->item->minimum_quantity_threshold;
                    $balance = $item_in_stock->balance;
                    if ($balance <= $minimum_quantity_threshold) {
                        $action = 'automatic restock request initiated';
                        if ($warehouse->id === $supplying_warehouse->id) {
                            $action = 'restock required';
                        }
                        $depletions[] = [
                            'warehouse' => $warehouse->name,
                            'item' => $item_in_stock->item->name,
                            'balance' =>  $balance,
                            'action' => $action,
                        ];

                        if ($warehouse->id !== $supplying_warehouse->id) {
                            $request_items[] = [
                                'item_id' => $item_in_stock->item_id,
                                'quantity' => $item_in_stock->item->auto_restock_quantity,
                            ];
                        }
                    } else if ($balance <= ($minimum_quantity_threshold + 10)) {
                        $depletions[] = [
                            'warehouse' => $warehouse->name,
                            'item' => $item_in_stock->item->name,
                            'balance' => $balance,
                            'action' => 'Mere Warning',
                        ];
                    }
                }
                if ($warehouse->id !== $supplying_warehouse->id) {
                    $collection = collect([
                        'request_warehouse_id' => $warehouse->id,
                        'supply_warehouse_id' => $supplying_warehouse->id,
                        'auto_genetated' => 'Auto Generated',
                        'status' => 'pending',
                        'notes' => 'Auto generated restock request for depleted quantity',
                        'request_items' => $request_items,
                    ]);
                    // process the automatic restocking for non-main warehouses


                    // return $collection;
                    $this->autoStore($collection);
                }
            }
        }
        $title = "Product Stock Depletion Notice";
        $description = 'Some warehouses have reached the minimum threshold of products in stock.';
        $roles = []; // ['assistant admin', 'warehouse manager', 'warehouse auditor'];
        $this->notifyProductDepletion($title, $description, 'inbound/item-stocks', $depletions, $roles);
        return $depletions;
    }
}
