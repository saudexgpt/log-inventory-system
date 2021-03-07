<template>
  <section class="invoice">
    <div v-if="!print_waybill">
      <div class="row">
        <div class="col-xs-12 page-header" align="center">
          <img src="/svg/logo.png" alt="Company Logo" width="150">
          <label>{{ companyName }}</label>
          <!-- <span v-if="waybill.trips.length > 0"> -->
          <span v-if="waybill.confirmed_by !== null">
            <div v-if="waybill.status === 'pending'" class="pull-right no-print">
              <a v-if="checkPermission(['manage waybill'])" @click="form.status = 'in transit'; changeWaybillStatus(); ">
                <i class="el-icon-printer" /> Print Waybill
              </a>
            </div>
            <div v-else class="pull-right no-print">
              <a v-if="checkPermission(['manage waybill'])" @click="print_waybill = true;">
                <i class="el-icon-printer" /> Print Waybill
              </a>
            </div>
          </span>
          <span v-else><h4 class="alert alert-danger">Items on this Waybill need confirmation by an Auditor</h4></span>
          <!-- </span> -->
        </div>
        <!-- /.col -->
      </div>
      <!-- info row -->
      <div class="row invoice-info" align="center">
        <span v-html="companyContact" />
        <legend>WAYBILL/DELIVERY NOTE</legend>
      </div>
      <div class="row">
        <div class="col-xs-8 table-responsive">
          <label>Customer Details</label>
          <address>
            <label>{{ waybill.invoices[0].customer.name.toUpperCase() }}</label><br><br>
            {{ waybill.invoices[0].customer.address }}
          </address>
          <legend>Invoice Products</legend>
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
                <!-- <th>Customer</th> -->
                <th>Product</th>
                <th>Quantity</th>
                <th>Batch No.</th>
                <!-- <th>Expires</th> -->
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
                <td>{{ waybill_item.invoice.invoice_number }}</td>
                <!-- <td>{{ waybill_item.invoice.customer.user.name.toUpperCase() }}</td> -->
                <td>{{ waybill_item.item.name }}</td>
                <!-- <td>{{ waybill_item.item.description }}</td> -->
                <td>{{ waybill_item.quantity }}
                </td>
                <td>
                  <div v-for="(batch, batch_index) in waybill_item.invoice_item.batches" :key="batch_index">
                    <span v-if="batch.to_supply === waybill_item.quantity">
                      {{ batch.item_stock_batch.batch_no }}
                    </span>
                  </div>
                </td>
                <!-- <td>
                  <div v-for="(batch, batch_index) in waybill_item.invoice_item.batches" :key="batch_index">
                    <span v-if="batch.to_supply === waybill_item.quantity">
                      {{ moment(batch.item_stock_batch.expiry_date).format('MMMM Do YYYY') }}
                    </span>
                  </div>
                </td> -->
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
          <label>Waybill No.: {{ waybill.waybill_no }}</label><br>
          <label>Date:</label>
          {{ moment(waybill.created_at).format('MMMM Do YYYY') }}
          <div v-if="waybill.trips.length > 0">
            <table class="table table-bordered">
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
      <div v-if="waybill.trips.length > 0">
        <div class="row">
          <div class="col-md-6 col-xs-12">
            <label align="center">CURRENT GOODS DELIVERY STATUS</label>
            <div v-if="waybill.status === 'pending'" align="center">
              <img src="/images/pending.png" alt="Pending" width="150">
              <br>
              <label>Goods delivery is pending</label>
            </div>
            <div v-else-if="waybill.status === 'in transit'" align="center">
              <img src="/images/transit.png" alt="Transition" width="150">
              <br>
              <label>Goods are currently in transit for delivery</label>
            </div>
            <div v-else-if="waybill.status === 'delivered'" align="center">
              <img src="/images/delivered.png" alt="Delivered" width="150">
              <br>
              <label>Goods are delivered</label>
            </div>
          </div>
          <div class="col-md-6 col-xs-12">
            <div v-if="waybill.status === 'pending'">
              <a
                class="btn btn-primary"
                @click="form.status = 'in transit'; changeWaybillStatus()"
              > <i class="el-icon-printer" /> Print Waybill</a>
              <span
                class="label label-danger"
              >This should be done only when goods have left the warehouse to meet the customer</span>
            </div>
            <div v-else-if="waybill.status === 'in transit'">
              <a
                class="btn btn-success"
                @click="form.status = 'delivered'; changeWaybillStatus()"
              >Click to Mark Goods as Delivered</a>
              <span
                class="label label-danger"
              >This should be done only when goods have been delivered successfully to the customer</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <print-waybill
      v-if="print_waybill"
      :waybill="waybill"
      :company-name="companyName"
      :currency="currency"
      :company-contact="companyContact"
    />
  </section>
</template>

<script>
import moment from 'moment';
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';
import Resource from '@/api/resource';
const confirmWaybillDetailsResource = new Resource('audit/confirm/waybill');
const changeWaybillStatusResource = new Resource('invoice/waybill/change-status');
import PrintWaybill from './PrintWaybill';
export default {
  components: { PrintWaybill },
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
      default: () => 'Warehouse Management System',
    },
    companyContact: {
      type: String,
      default: () => '',
    },
    currency: {
      type: String,
      default: () => '₦',
    },
  },
  data() {
    return {
      activeActivity: 'first',
      form: {
        status: 'pending',
      },
      print_waybill: false,
      confirmed_items: [],
      activate_confirm_button: false,
    };
  },
  mounted() {
    // this.doPrint();
  },
  methods: {
    checkPermission,
    checkRole,
    moment,
    changeWaybillStatus(){
      const app = this;
      var param = app.waybill;
      const message = 'Do you really want to update goods delivery status to: ' + app.form.status.toUpperCase() + '?';
      if (confirm(message)) {
        param.status = app.form.status;
        changeWaybillStatusResource.update(param.id, param)
          .then(response => {
            app.waybill.status = app.form.status;
            if (app.form.status === 'in transit') {
              app.print_waybill = true;
            }
          });
      }
    },
    activateConfirmButton() {
      this.activate_confirm_button =
      this.waybill.waybill_items.length === this.confirmed_items.length;
    },
    confirmWaybillDetails() {
      const app = this;
      var param = { waybill_item_ids: app.confirmed_items };
      const message = 'Are you sure everything is intact? Click OK to submit.';
      if (confirm(message)) {
        param.status = app.form.status;
        confirmWaybillDetailsResource
          .update(app.waybill.id, param)
          .then(response => {
            app.waybill.status = app.form.status;

            if (response.confirmed === 'success') {
              app.activate_confirm_button = false;
              app.waybill.confirmed_by = 23;
              app.$message('Waybill Items Confirmed Successfully');
            }
          });
      }
    },
    formatPackageType(type) {
      // var formated_type = type + 's';
      // if (type === 'Box') {
      //   formated_type = type + 'es';
      // }
      return type;
    },
  },
};
</script>
