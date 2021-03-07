<template>
  <div class="app-container">
    <!-- <item-details v-if="page.option== 'view_details'" :item-in-stock="order" :page="page" /> -->
    <!-- <add-new v-if="page.option== 'add_new'" :orders="orders" :params="params" :page="page" />
    <edit-item v-if="page.option=='edit_item'" :orders="orders" :order="order" :params="params" :page="page" @update="onEditUpdate" /> -->
    <div v-if="page.option==='list'" class="box">
      <div class="box-header">
        <h4 class="box-title">List of Orders {{ in_warehouse }}</h4>

        <span class="pull-right">
          <router-link v-if="checkPermission(['create order'])" :to="{name:'Create'}" class="btn btn-info"> Create New Order</router-link>
        </span>

      </div>
      <div class="box-body">
        <aside>
          <el-row>
            <el-col :xs="24" :sm="12" :md="12">
              <p for="">Select Warehouse</p>
              <el-select v-model="form.warehouse_index" placeholder="Select Warehouse" class="span" filterable @input="getOrders">
                <el-option v-for="(warehouse, index) in warehouses" :key="index" :value="index" :label="warehouse.name" />

              </el-select>

            </el-col>
            <el-col :xs="24" :sm="12" :md="12">
              <p for="">Select status</p>
              <el-select v-model="form.status" placeholder="Select Status" class="span" @input="getOrders">
                <el-option v-for="(status, index) in order_statuses" :key="index" :value="status.code" :label="status.name" />

              </el-select>

            </el-col>
          </el-row>
        </aside>
        <v-client-table v-model="orders" :columns="columns" :options="options">
          <div slot="amount" slot-scope="props">
            {{ props.row.currency.code + Number(props.row.amount).toLocaleString() }}
          </div>
          <div slot="created_at" slot-scope="props">
            {{ moment(props.row.created_at).format('MMMM Do, YYYY') }}
          </div>
          <div slot="action" slot-scope="props">
            <el-tooltip class="item" effect="dark" content="View Order Details" placement="bottom-start">
              <a v-if="props.row.order_status !== 'cancelled'" class="btn btn-primary" @click="order=props.row; page.option='order_details'; selected_row_index = props.index"><i class="el-icon-view" /></a>
            </el-tooltip>
            <el-tooltip class="item" effect="dark" content="Create Invoice" placement="bottom">
              <a v-if="props.row.order_status === 'pending' && checkPermission(['create invoice'])" class="btn btn-success" @click="createOrderInvoice(props.index, props.row); selected_row_index = props.index"><i class="el-icon-tickets" /></a>
            </el-tooltip>
            <el-tooltip class="item" effect="dark" content="Restore Order" placement="bottom">
              <a v-if="props.row.order_status === 'cancelled' && checkPermission(['cancel order'])" class="btn btn-warning" @click="restoreOrder(props.index, props.row);"><i class="el-icon-refresh-left" /></a>
            </el-tooltip>
            <el-tooltip class="item" effect="dark" content="Cancel Order" placement="bottom-end">
              <a v-if="props.row.order_status === 'pending' && checkPermission(['cancel order'])" class="btn btn-danger" @click="cancelOrder(props.index, props.row);"><i class="el-icon-close" /></a>
            </el-tooltip>
            <!-- <el-dropdown class="avatar-container right-menu-item hover-effect" trigger="click">
              <div class="avatar-wrapper" style="color: brown">
                <label style="cursor:pointer"><i class="el-icon-more-outline" /></label>
              </div>
              <el-dropdown-menu slot="dropdown" style="padding: 10px;">
                <el-dropdown-item v-if="props.row.order_status === 'pending' && checkPermission(['approve order'])">
                  <a @click="approveOrder(props.index, props.row);">Approve</a>
                </el-dropdown-item>
                <el-dropdown-item v-if="props.row.order_status === 'approved' && checkPermission(['approve order', 'deliver order'])" divided>
                  <a @click="deliverOrder(props.index, props.row);">Delivered</a>
                </el-dropdown-item>
                <el-dropdown-item v-if="props.row.order_status === 'pending' && checkPermission(['cancel order'])" divided>
                  <a @click="cancelOrder(props.index, props.row);">Cancel</a>
                </el-dropdown-item>
              </el-dropdown-menu>
            </el-dropdown> -->
          </div>

        </v-client-table>

      </div>

    </div>
    <div v-if="page.option==='order_details'">
      <a class="btn btn-danger no-print" @click="page.option='list'">Go Back</a>
      <a v-if="order.order_status === 'pending' && checkPermission(['create invoice'])" class="btn btn-success" @click="createOrderInvoice(selected_row_index, order);"><i class="el-icon-tickets" />Create invoice</a>
      <order-details :order="order" :page="page" :company-name="params.company_name" />
    </div>
  </div>
</template>
<script>
import moment from 'moment';
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';
import OrderDetails from './Details';
import Resource from '@/api/resource';
// import Vue from 'vue';
const necessaryParams = new Resource('fetch-necessary-params');
const fetchOrders = new Resource('order/general');
const approveOrderResource = new Resource('order/general/approve');
const deliverOrderResource = new Resource('order/general/deliver');
const cancelOrderResource = new Resource('order/general/cancel');
const createOrderinvoiceResource = new Resource('invoice/general/store');
// const deleteItemInStock = new Resource('stock/items-in-stock/delete');
export default {
  components: { OrderDetails },
  data() {
    return {
      warehouses: [],
      orders: [],
      order_statuses: [],
      columns: ['action', 'order_number', 'site.name', 'created_at', 'order_status'],

      options: {
        headings: {
          'site.name': 'Order By',
          order_number: 'Order Number',
          created_at: 'Date',
          order_status: 'Status',

          // id: 'S/N',
        },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['order_number', 'site.name', 'created_at', 'order_status'],
        filterable: ['order_number', 'site.name', 'created_at', 'order_status'],
      },
      page: {
        option: 'list',
      },
      params: [],
      form: {
        warehouse_index: '',
        warehouse_id: '',
        status: 'pending',
      },
      in_warehouse: '',
      order: {},
      selected_row_index: '',

    };
  },

  mounted() {
    this.fetchNecessaryParams();
  },
  beforeDestroy() {

  },
  methods: {
    moment,
    checkPermission,
    checkRole,
    fetchNecessaryParams() {
      const app = this;
      necessaryParams.list()
        .then(response => {
          app.params = response.params;
          app.warehouses = response.params.warehouses;
          app.order_statuses = response.params.order_statuses;
          if (app.warehouses.length > 0) {
            app.form.warehouse_id = app.warehouses[0];
            app.form.warehouse_index = 0;
            app.getOrders();
          }
        });
    },
    getOrders() {
      const app = this;
      const loader = fetchOrders.loaderShow();

      const param = app.form;
      param.warehouse_id = app.warehouses[param.warehouse_index].id;
      fetchOrders.list(param)
        .then(response => {
          app.orders = response.orders;
          app.in_warehouse = 'for ' + app.warehouses[param.warehouse_index].name;
          loader.hide();
        })
        .catch(error => {
          loader.hide();
          console.log(error.message);
        });
    },
    deliverOrder(index, order){
      const app = this;
      const param = { status: 'delivered' };
      deliverOrderResource.update(order.id, param)
        .then(response => {
          app.orders.splice(index - 1, 1);
        });
    },
    approveOrder(index, order){
      const app = this;
      const param = { status: 'approved' };
      approveOrderResource.update(order.id, param)
        .then(response => {
          app.orders.splice(index - 1, 1);
        });
    },
    cancelOrder(index, order){
      const app = this;
      if (confirm('Cancel Order? Click to confirm')) {
        const param = { status: 'cancelled' };
        cancelOrderResource.update(order.id, param)
          .then(response => {
            app.orders.splice(index - 1, 1);
          });
      }
    },
    restoreOrder(index, order){
      const app = this;
      if (confirm('Restore Order? Click to confirm')) {
        const param = { status: 'pending' };
        cancelOrderResource.update(order.id, param)
          .then(response => {
            app.orders.splice(index - 1, 1);
          });
      }
    },
    processOrder(index, order){
      const app = this;
      const param = { status: 'processed' };
      cancelOrderResource.update(order.id, param)
        .then(response => {
          app.order.order_status = 'processed';
          app.orders.splice(index - 1, 1);
        });
    },
    createOrderInvoice(index, order){
      const app = this;
      if (confirm('Create Invoice for this Order: ' + order.order_number + '? Click to confirm')) {
        var param;
        param = order;
        param.invoice_items = order.order_items;
        param.customer_id = order.site_id;
        param.order_id = order.id;
        param.invoice_date = order.created_at;
        param.status = order.order_status;
        const loader = createOrderinvoiceResource.loaderShow();
        createOrderinvoiceResource.store(param)
          .then(response => {
            app.processOrder(index, order);
            app.$message('Order Processed and Invoice Created');
            loader.hide();
          });
      }
    },

  },
};
</script>
