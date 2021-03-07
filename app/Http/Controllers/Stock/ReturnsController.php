<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\ItemStockSubBatch;
use App\Models\Stock\ReturnedProduct;
use App\Models\Stock\ReturnedProductImage;
use Illuminate\Http\Request;
use App\Models\Stock\SiteItemStock;
use App\Models\Warehouse\Site;
use App\Models\Warehouse\Warehouse;
use Image;
use Illuminate\Support\Facades\Storage;

class ReturnsController extends Controller
{
    //
    public function uploadImage(Request $request)
    {
        // return $request;
        $site_id = $request->site_id;
        $item_id = $request->item_id;
        if ($request->hasFile('images') && $request->file('images')->isValid()) {
            $img_url = '';
            // foreach ($request->file('images') as $image) {
            $image = $request->file('images');
            $uid = $request->uid;
            $valid_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array(strtolower($image->getClientOriginalExtension()), $valid_extensions)) {
                return ['success' => 0, 'msg' => implode(',', $valid_extensions) . ' ' . trans('app.valid_extension_msg')];
            }

            $file_base_name = str_replace('.' . $image->getClientOriginalExtension(), '', $image->getClientOriginalName());

            $resized = Image::make($image)->resize(null, 250, function ($constraint) {
                $constraint->aspectRatio();
            })->stream();
            // $resized_thumb = Image::make($image)->resize(420, 270)->stream();
            // $resized_thumb = Image::make($image)->resize(null, 270, function ($constraint) {
            //     $constraint->aspectRatio();
            // })->stream();
            $image_name = strtolower(time() . str_random(5) . '-' . str_slug($file_base_name)) . '.' . $image->getClientOriginalExtension();

            $imageFileName = 'uploads/images/' . $image_name;
            // $imageThumbName = 'uploads/images/thumbs/' . $image_name;

            try {
                //Upload original image
                $is_uploaded = current_disk()->put($imageFileName, $resized->__toString(), 'public');

                if ($is_uploaded) {
                    //Save image name into db
                    $created_img_db = ReturnedProductImage::create(['uid' => $uid, 'site_id' => $site_id, 'item_id' => $item_id, 'media_name' => $image_name, 'type' => 'image', 'storage' => 'public']);

                    //upload thumb image
                    // current_disk()->put($imageThumbName, $resized_thumb->__toString(), 'public');
                    $img_url = media_url($created_img_db);
                    return ['success' => 1, 'img_url' => $img_url, 'img_id' => $created_img_db->id];
                    // print_r(['success' => 1, 'img_url' => $img_url]);
                } else {
                    return ['success' => 0, "message" => "not working"];
                }
            } catch (\Exception $e) {
                return $e->getMessage();
            }
            // }


        }
        return ['success' => 0, "message" => "No Image file detected"];
    }
    public function deleteMedia(Request $request)
    {
        // return $request;
        $uid = $request->uid;
        $media = ReturnedProductImage::where('uid', $uid)->first();
        // $media = Media::where('media_name', $uid)->first();

        $storage = Storage::disk($media->storage);
        if ($storage->has('uploads/images/' . $media->media_name)) {
            $storage->delete('uploads/images/' . $media->media_name);
        }

        $media->delete();
        return ['success' => 1, 'msg' => trans('app.media_deleted_msg')];
    }
    public function index(Request $request)
    {
        //
        $site_id = $request->site_id;
        $returned_products = ReturnedProduct::with(['warehouse', 'site', 'stock.subBatch', 'item', 'stocker', 'confirmer'])->where('site_id', $site_id)->where('quantity', '>', 'quantity_approved')->orderBy('id', 'DESC')->get();

        // $items_in_stock = ItemStock::with(['warehouse', 'item'])->groupBy('item_id')->having('warehouse_id', $warehouse_id)
        // ->select('*',\DB::raw('SUM(quantity) as total_quantity'))->get();
        return response()->json(compact('returned_products'));
    }
    public function siteProductBatches(Request $request)
    {
        //
        $site_id = $request->site_id;
        $item_id = $request->item_id;
        $batches_of_items_in_stock = SiteItemStock::with('subBatch')->where(['site_id' => $site_id, 'item_id' => $item_id])->whereRaw('balance > 0')->get();
        return response()->json(compact('batches_of_items_in_stock'));
    }
    public function store(Request $request)
    {
        //
        $user = $this->getUser();
        $warehouse_id = Site::find($request->site_id)->warehouse_id;
        $returned_product = new ReturnedProduct();
        $returned_product->warehouse_id  = $warehouse_id;
        $returned_product->site_id  = $request->site_id;
        $returned_product->item_id       = $request->item_id;
        $returned_product->site_item_stock_id      = $request->site_item_stock_id;
        $returned_product->date_returned   = $request->date_returned;
        $returned_product->quantity      = $request->quantity;
        $returned_product->reason        = $request->reason;
        if ($request->reason == 'Others' && $request->other_reason != null) {
            $returned_product->reason  = $request->other_reason;
        }

        $returned_product->stocked_by      = $user->id;
        if ($returned_product->save()) {

            // deduct form stock
            $stock = $returned_product->stock;
            $stock->returned += $returned_product->quantity;
            $stock->balance -= $returned_product->quantity;
            $stock->save();
            // update media table
            $uploaded_media = json_decode(json_encode($request->uploaded_media));
            foreach ($uploaded_media as $media) {
                $uid = $media->uid;
                $image = ReturnedProductImage::where('uid', $uid)->first();
                $image->returned_product_id = $returned_product->id;
                $image->save();
            }
        }

        $title = "Products returned";
        $description = "Products were returned by " . $returned_product->site->name . " Details uploaded by $user->name";
        //log this activity
        $roles = ['assistant admin', 'warehouse manager', 'warehouse auditor', 'stock officer'];
        $this->logUserActivity($title, $description, $roles);
        return $this->show($returned_product);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Stock\ItemStock  $itemStock
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ReturnedProduct $returned_product)
    {
        //
        $returned_product->item_id       = $request->item_id;
        $returned_product->batch_no      = $request->batch_no;
        $returned_product->customer_name = $request->customer_name;
        $returned_product->expiry_date   = $request->expiry_date;
        $returned_product->date_returned = $request->date_returned;
        $returned_product->quantity      = $request->quantity;
        $returned_product->reason        = $request->reason;
        if ($request->reason == 'Others' && $request->other_reason != null) {
            $returned_product->reason  = $request->other_reason;
        }

        $returned_product->save();

        $user = $this->getUser();
        $title = "Returned products updated";
        $description = "Product returned with entry id: " . $returned_product->id . " was modified by $user->name ($user->email)";
        //log this activity
        $roles = ['assistant admin', 'warehouse manager', 'warehouse auditor', 'stock officer'];
        $this->logUserActivity($title, $description, $roles);
        return $this->show($returned_product);
    }
    public function show(ReturnedProduct $returned_product)
    {

        $returned_product = $returned_product->with(['warehouse', 'site', 'stock.subBatch', 'item', 'stocker', 'confirmer'])->find($returned_product->id);
        return response()->json(compact('returned_product'), 200);
    }
    public function destroy(Request $request, ReturnedProduct $returned_product)
    {
        //
        $stock = $returned_product->stock;
        $stock->returned -= $returned_product->quantity;
        $stock->balance += $returned_product->quantity;
        $stock->save();
        $images = $returned_product->images;
        foreach ($images as $image) {
            $request->uid = $image->uid;
            $this->deleteMedia($request);
        }

        $returned_product->delete();
        $user = $this->getUser();
        $title = "Returned products deleted";
        $description = "Product returned with entry id: " . $returned_product->id . " was deleted by $user->name";
        //log this activity
        $roles = ['assistant admin', 'warehouse manager', 'warehouse auditor', 'stock officer'];
        $this->logUserActivity($title, $description, $roles);
        return $this->show($returned_product);
    }

    public function approveReturnedProducts(Request $request)
    {
        // Virtual Warehouse ID is 3
        $user = $this->getUser();
        $warehouse_id = 3;
        $warehouse = Warehouse::find(3);
        $approved_quantity = $request->approved_quantity;
        $details = json_decode(json_encode($request->product_details));
        // update the approved quantity
        $returned_prod = ReturnedProduct::find($details->id);

        $returned_prod->quantity_approved += $approved_quantity;
        if ($returned_prod->quantity >= $returned_prod->quantity_approved) {
            $returned_prod->confirmed_by = $user->id;
            $returned_prod->save();

            $item_stock_sub_batch = new ItemStockSubBatch();
            $item_stock_sub_batch->stocked_by = $user->id;
            $item_stock_sub_batch->confirmed_by = $user->id;
            $item_stock_sub_batch->warehouse_id = $warehouse_id;
            $item_stock_sub_batch->item_id = $returned_prod->item_id;
            $item_stock_sub_batch->batch_no = $returned_prod->stock->subBatch->batch_no;
            $item_stock_sub_batch->sub_batch_no = $returned_prod->stock->subBatch->batch_no;
            $item_stock_sub_batch->part_number = $returned_prod->stock->subBatch->part_number;

            $item_stock_sub_batch->quantity = $approved_quantity;
            $item_stock_sub_batch->reserved_for_supply = 0;
            $item_stock_sub_batch->in_transit = 0; // initial values set to zero
            $item_stock_sub_batch->supplied = 0;
            $item_stock_sub_batch->balance = $approved_quantity;
            $item_stock_sub_batch->save();



            $title = "Returned products approval";
            $description = "Product returned with batch no: " . $item_stock_sub_batch->batch_no . " and part no: " . $item_stock_sub_batch->part_number . " from " . $returned_prod->site->name . " was approved by $user->name and sent to " . $warehouse->name;
            //log this activity
            $roles = ['assistant admin', 'warehouse manager', 'warehouse auditor', 'stock officer'];
            $this->logUserActivity($title, $description, $roles);
        }

        return $this->show($returned_prod);
    }
}
