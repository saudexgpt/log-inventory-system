<template>
  <div class="clear-margin">
    <!-- title row -->
    <div class="row">
      <div class="col-xs-12 page-header" align="center">
        <img src="/svg/logo.png" alt="Company Logo" width="150">
        <span>
          <label>{{ companyName }}</label>
          <div class="pull-right no-print">
            <a v-if="checkPermission(['manage waybill'])" @click="doPrint();"><i class="el-icon-printer" /> Print Waybill</a>
          </div>
        </span>
      </div>
      <!-- /.col -->
    </div>
    <!-- info row -->
    <div class="row invoice-info" align="center">
      <span v-html="companyContact" />
      <legend>WAYBILL/DELIVERY NOTE</legend>
    </div>
    <!-- /.row -->

    <!-- Table row -->
    <div class="row">
      <div class="col-xs-8 table-responsive">
        <label>Recipient Details</label>
        <address>
          <label>{{ waybill.transfer_requests[0].request_warehouse.name.toUpperCase() }}</label>
        </address>
        <legend>Products</legend>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>
                <div
                  v-if="waybill.confirmed_by === null && checkPermission(['audit confirm actions'])"
                >Confirm Items</div>
                <div v-else>S/N</div>
              </th>
              <th>Invoice No.</th>
              <th>Request By</th>
              <th>Product</th>
              <th>Quantity</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(waybill_item, index) in waybill.waybill_items" :key="index">
              <td>
                <div :id="waybill_item.id">
                  <div
                    v-if="waybill_item.is_confirmed === '0' && checkPermission(['audit confirm actions'])"
                  >
                    <input
                      v-model="confirmed_items"
                      :value="waybill_item.id"
                      type="checkbox"
                      @change="activateConfirmButton()"
                    >
                  </div>
                  <div v-else>{{ index + 1 }}</div>
                </div>
              </td>
              <td>{{ waybill_item.invoice.request_number }}</td>
              <td>{{ waybill_item.invoice.request_by.name.toUpperCase() }}</td>
              <td>{{ waybill_item.item.name }}</td>
              <!-- <td>{{ waybill_item.item.description }}</td> -->
              <td>{{ waybill_item.quantity }}</td>
              <!-- <td align="right">{{ currency + Number(waybill_item.rate).toLocaleString() }}</td>
                <td>{{ waybill_item.type }}</td>
                <td align="right">{{ currency + Number(waybill_item.amount).toLocaleString() }}</td>-->
            </tr>
            <!-- <tr>
                <td colspan="4" align="right"><label>Subtotal</label></td>
                <td align="right">{{ currency + Number(waybill.invoice.subtotal).toLocaleString() }}</td>
              </tr>
              <tr>
                <td colspan="4" align="right"><label>Discount</label></td>
                <td align="right">{{ currency + Number(waybill.invoice.discount).toLocaleString() }}</td>
              </tr>
              <tr>
                <td colspan="4" align="right"><label>Grand Total</label></td>
                <td align="right"><label style="color: green">{{ currency + Number(waybill.invoice.amount).toLocaleString() }}</label></td>
              </tr>-->
          </tbody>
        </table>
        <a
          v-if="checkPermission(['audit confirm actions']) && activate_confirm_button"
          class="btn btn-success"
          title="Click to confirm"
          @click="confirmWaybillDetails()"
        >
          <i class="fa fa-check" /> Click to save confirmation
        </a>
      </div>
      <div class="col-xs-4 table-responsive">
        <label>Waybill No.: {{ waybill.transfer_request_waybill_no }}</label>
        <br>
        <label>Date:</label>
        {{ moment(waybill.created_at).format('MMMM Do YYYY') }}<br>
        <!-- <el-select v-if="warehouseId === waybill.supply_warehouse_id" v-model="waybill.dispatched_by" style="width: 100%" filterable @change="setDispatcher($event)">
            <el-option v-for="(driver, driver_index) in drivers" :key="driver_index" :value="(driver.user) ? driver.user.id : ''" :label="(driver.user) ? driver.user.name : ''" />
          </el-select> -->
        <div v-if="waybill.trips.length > 0">
          <table v-if="waybill.trips.length > 0" class="table table-bordered">
            <tbody>
              <tr>
                <td>
                  <label>Dispatched By:</label>
                  {{ waybill.trips[0].dispatch_company }}
                </td>
              </tr>
              <tr>
                <td>
                  <label>Trip No.:</label>
                  {{ waybill.trips[0].trip_no }}
                </td>
              </tr>
              <tr>
                <td>
                  <label>Vehicle No.:</label>
                  {{ waybill.trips[0].vehicle_no }}
                </td>
              </tr>
              <tr>
                <td>
                  <label>Dispatchers:</label>
                  {{ waybill.trips[0].dispatchers }}
                </td>
              </tr>
              <tr>
                <td>
                  <label>Description:</label>
                  {{ waybill.trips[0].description }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else>
          <span v-if="checkPermission(['manage waybill cost'])">
            <p>You need to add this waybill to a vehicle for delivery. Click the button below to do so</p>
            <router-link :to="{name:'WaybillDeliveryCost'}" class="btn btn-default"> Add Waybill to Trip</router-link>
          </span>
        </div>
      </div>
      <!-- /.col -->
    </div>
    <div class="row">
      <div class="col-xs-12">
        <span>Confirm the receipt of the above listed goods in good condition and complete</span><br>
        <span>Which are not returnable</span><br><br><br>
      </div>
      <div class="col-xs-7">
        <label>Name: ______________________________</label><br><br><br>
        <label>Sign: ______________________________</label>
      </div>
      <div class="col-xs-5">
        <label>Date: _______________________</label><br><br><br>
        <label>Time: _______________________</label>
      </div>
    </div>
  </div>
</template>

<script>
import moment from 'moment';
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';
// import Watermark from '@/watermark';
export default {
  props: {
    waybill: {
      type: Object,
      default: () => ({}),
    },
    page: {
      type: Object,
      default: () => ({
        option: 'print_waybill',
      }),
    },
    companyName: {
      type: String,
      default: () => ('Warehouse Management System'),
    },
    companyContact: {
      type: String,
      default: () => (''),
    },
    currency: {
      type: String,
      default: () => ('₦'),
    },
  },
  data() {
    return {
      activeActivity: 'first',
    };
  },
  mounted() {
    // Watermark.set('Green Life Pharmaceutical Ltd.');
    this.doPrint();
  },
  methods: {
    checkPermission,
    checkRole,
    moment,
    doPrint() {
      window.print();
    },
  },
};
</script>
<style>
@media print {
* {
    -webkit-print-color-adjust: exact !important; /*Chrome, Safari */
    color-adjust: exact !important;  /*Firefox*/

  }
  .clear-margin {
    margin-top: -100px !important;
    background: url("../../../../../public/svg/watermark.png");
  }
}
</style>
