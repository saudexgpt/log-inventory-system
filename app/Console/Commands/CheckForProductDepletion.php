<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Http\Traits\GoodsTransferTrait;
use App\Laravue\Models\User;
use App\Models\Stock\ItemStockSubBatch;
use App\Models\Warehouse\Warehouse;
use Notification;
use App\Notifications\NotifyProductDepletion;
use App\Notifications\NotifyWithEmailDB;
use Illuminate\Http\Request;

class CheckForProductDepletion extends Command
{

    use GoodsTransferTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:depletion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function notifyProductDepletion($title, $description, $url, $depletions, $roles = [])
    {

        $url = env('APP_URL') . '/' . $url;
        $users = $this->setupRecipients($roles);
        $delay = now()->addMinutes(2);
        // var_dump($users);
        $notification = new NotifyProductDepletion($title, $description, $url, $depletions);
        return Notification::send($users->unique(), $notification); //->delay($delay);
    }
    private function setupRecipients($roles = [])
    {
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', '=', 'admin'); // this is the role id inside of this callback
        })->get();

        if (in_array('assistant admin', $roles)) {
            $assistant_admin = User::whereHas('roles', function ($query) {
                $query->where('name', '=', 'assistant admin'); // this is the role id inside of this callback
            })->get();
            $users = $users->merge($assistant_admin);
        }
        if (in_array('warehouse manager', $roles)) {
            $warehouse_managers = User::whereHas('roles', function ($query) {
                $query->where('name', '=', 'warehouse manager'); // this is the role id inside of this callback
            })->get();
            $users = $users->merge($warehouse_managers);
        }
        if (in_array('stock officer', $roles)) {
            $stock_officers = User::whereHas('roles', function ($query) {
                $query->where('name', '=', 'stock officer'); // this is the role id inside of this callback
            })->get();
            $users = $users->merge($stock_officers);
        }
        if (in_array('warehouse auditor', $roles)) {
            $auditors = User::whereHas('roles', function ($query) {
                $query->where('name', '=', 'warehouse auditor'); // this is the role id inside of this callback
            })->get();
            $users = $users->merge($auditors);
        }

        return $users;
    }
    private function checkForProductDepletion()
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
    public function handle()
    {
        //
        $this->checkForProductDepletion();
    }
}
