<template>
  <div class="app-container">
    <!-- <item-details v-if="page.option== 'view_details'" :item-in-stock="itemInStock" :page="page" /> -->
    <add-new v-if="page.option== 'add_new'" :items-in-stock="items_in_stock" :params="params" :page="page" @save="onSave" />
    <edit-item v-if="page.option=='edit_item'" :items-in-stock="items_in_stock" :item-in-stock="itemInStock" :params="params" :page="page" @update="onEditUpdate" />

    <div v-if="page.option== 'bulk_upload'">
      <a class="btn btn-default" @click="page.option = 'add_new'"> Cancel</a>
      <bulk-upload :items-in-stock="itemsInStock" :params="params" :page="page" @save="onSave" />
    </div>
    <div v-if="page.option=='list'" class="box">
      <div class="box-header">
        <h4 class="box-title">List of Products {{ in_warehouse }}</h4>

        <span class="pull-right">
          <a v-if="checkPermission(['manage item stocks', 'create item stocks'])" class="btn btn-info" @click="page.option = 'add_new'"> Add New</a>
        </span>

      </div>
      <div class="box-body">
        <el-col :xs="24" :sm="12" :md="12">
          <label for="">Select Warehouse</label>
          <el-select v-model="form.warehouse_index" placeholder="Select Warehouse" class="span" filterable @input="fetchItemStocks">
            <el-option v-for="(warehouse, index) in warehouses" :key="index" :value="index" :label="warehouse.name" />

          </el-select>

        </el-col>
        <br><br><br><br>
        <div>
          <el-button :loading="downloadLoading" style="margin:0 0 20px 20px;" type="primary" icon="document" @click="handleDownload">
            Export Excel
          </el-button>
        </div>

        <v-client-table v-model="items_in_stock" :columns="columns" :options="options">
          <div slot="child_row" slot-scope="props">
            <label>Break down of {{ props.row.item.name +' (' + props.row.batch_no + ')' }} among sites</label>
            <aside>
              <v-client-table
                v-model="props.row.site_item_stocks"
                :columns="['site.name', 'quantity', 'used', 'returned', 'balance']"
                :options="child_options"
              >
                <div slot="quantity" slot-scope="{row}" class="alert alert-info">
                  {{ row.quantity }}

                </div>
                <div slot="returned" slot-scope="{row}" class="alert alert-warning">
                  {{ row.returned }}

                </div>
                <div slot="used" slot-scope="{row}" class="alert alert-danger">
                  {{ row.used }}

                </div>
                <div slot="balance" slot-scope="{row}" class="alert alert-success">
                  {{ row.balance }}

                </div>
              </v-client-table>
            </aside>
          </div>
          <div slot="quantity" slot-scope="{row}" class="alert alert-info">
            {{ row.quantity }}

          </div>
          <div slot="in_transit" slot-scope="{row}" class="alert alert-warning">
            {{ row.in_transit }}

          </div>
          <div slot="supplied" slot-scope="{row}" class="alert alert-danger">
            {{ row.supplied }}

          </div>
          <div slot="reserved_for_supply" slot-scope="{row}" class="alert alert-default">
            {{ row.reserved_for_supply }}

          </div>
          <div slot="in_stock" slot-scope="{row}" class="alert alert-primary">
            {{ row.balance }}

          </div>
          <div slot="balance" slot-scope="{row}" class="alert alert-success">
            {{ (row.balance - row.reserved_for_supply) }}

          </div>
          <!-- <div slot="expiry_date" slot-scope="{row}" :class="'alert alert-'+ expiryFlag(moment(row.expiry_date).format('x'))">
            <span>
              {{ moment(row.expiry_date).fromNow() }}
            </span>
          </div> -->
          <div slot="created_at" slot-scope="{row}">
            {{ moment(row.created_at).fromNow() }}
          </div>
          <div slot="confirmer.name" slot-scope="{row}">
            <div :id="row.id">
              <div v-if="row.confirmed_by == null">
                <a v-if="checkPermission(['audit confirm actions'])" title="Click to confirm" class="btn btn-warning" @click="confirmItemStocked(row.id);"><i class="fa fa-check" /> </a>
              </div>
              <div v-else>
                {{ row.confirmer.name }}
              </div>
            </div>
          </div>
          <div slot="action" slot-scope="props">
            <div v-if="props.row.is_warehouse_transfered === 0">
              <a v-if="checkPermission(['manage item stocks', 'update item stocks'])" class="btn btn-primary" @click="itemInStock=props.row; selected_row_index=props.index; page.option = 'edit_item'"><i class="fa fa-edit" /> </a>
              <a v-if="checkPermission(['manage item stocks', 'delete item stocks'])" class="btn btn-danger" @click="confirmDelete(props.index, props)"><i class="fa fa-trash" /> </a>

            </div>
            <div v-else>
              <a v-if="checkPermission(['manage item stocks', 'update item stocks'])" class="btn btn-dark"><i class="fa fa-edit" /> </a>
              <a v-if="checkPermission(['manage item stocks', 'delete item stocks'])" class="btn btn-dark"><i class="fa fa-trash" /> </a>
            </div>

            <!-- <a class="btn btn-default" @click="itemInStock=props.row; page.option = 'view_details'"><i class="fa fa-eye" /> </a> -->
            <!-- <a class="btn btn-warning" @click="itemInStock=props.row; selected_row_index=props.index; page.option = 'edit_item'"><i class="fa fa-edit" /> </a>
            <a class="btn btn-danger" @click="confirmDelete(props)"><i class="fa fa-trash" /> </a> -->
          </div>
        </v-client-table>

      </div>

    </div>

  </div>
</template>
<script>
import moment from 'moment';
import { parseTime } from '@/utils';
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';

import AddNew from './partials/AddNew';
import EditItem from './partials/EditItem';
import BulkUpload from './partials/BulkUpload';
// import ItemDetails from './partials/ItemDetails';
import Resource from '@/api/resource';
// import Vue from 'vue';
const necessaryParams = new Resource('fetch-necessary-params');
// const fetchWarehouse = new Resource('warehouse/fetch-warehouse');
const itemsInStock = new Resource('stock/items-in-stock');
const deleteItemInStock = new Resource('stock/items-in-stock/delete');
const confirmItemInStock = new Resource('audit/confirm/items-in-stock');
export default {
  components: { AddNew, EditItem, BulkUpload },
  data() {
    return {
      warehouses: [],
      items_in_stock: [],
      columns: ['action', 'confirmer.name', 'item.name', 'part_number', 'stocker.name', 'batch_no', /* 'expiry_date', */'quantity', 'in_transit', 'supplied', 'in_stock', 'reserved_for_supply', 'balance', 'created_at'],

      child_options: {
        headings: {
          'site.name': 'Site Name',
          quantity: 'Quantity Stocked',
        },
      },
      options: {
        headings: {
          'confirmer.name': 'Confirmed By',
          'stocker.name': 'Stocked By',
          'item.name': 'Product',
          batch_no: 'Batch No.',
          quantity: 'Quantity Stocked',
          in_transit: 'In Transit',
          supplied: 'Supplied',
          reserved_for_supply: 'Reserved for Supply',
          in_stock: 'Physical Stock',
          balance: 'Main Balance',
          // expiry_date: 'Expires',
          created_at: 'Created',

          // id: 'S/N',
        },
        pagination: {
          dropdown: true,
          chunk: 10,
        },
        filterByColumn: true,
        texts: {
          filter: 'Search:',
        },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['item.name', 'batch_no', /* 'quantity', 'in_transit', 'supplied', 'balance', 'expiry_date', */ 'created_at'],
        filterable: ['stocker.name', 'item.name', 'batch_no', 'created_at'],
      },
      page: {
        option: 'list',
      },
      params: {},
      form: {
        warehouse_index: '',
        warehouse_id: '',
      },
      in_warehouse: '',
      itemInStock: {},
      selected_row_index: '',
      product_expiry_date_alert_in_months: 9, // defaults to 9 months
      downloadLoading: false,
      filename: 'Products in Stock',

    };
  },

  mounted() {
    // this.getWarehouse();
    this.fetchNecessaryParams();
  },
  // beforeDestroy() {

  // },
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
          if (app.warehouses.length > 0) {
            app.form.warehouse_id = app.warehouses[0];
            app.form.warehouse_index = 0;
            app.fetchItemStocks();
          }
          app.product_expiry_date_alert_in_months = response.params.product_expiry_date_alert;
        });
    },
    confirmItemStocked(stock_id) {
      // const app = this;
      const form = { id: stock_id };
      const message = 'Click okay to confirm action';
      if (confirm(message)) {
        confirmItemInStock.update(stock_id, form)
          .then(response => {
            if (response.confirmed === 'success'){
              document.getElementById(stock_id).innerHTML = response.confirmed_by;
            }
          });
      }
    },
    fetchItemStocks() {
      const app = this;
      const loader = itemsInStock.loaderShow();

      const param = app.form;
      param.warehouse_id = app.warehouses[param.warehouse_index].id;
      itemsInStock.list(param)
        .then(response => {
          app.items_in_stock = response.items_in_stock;
          app.in_warehouse = 'in ' + app.warehouses[param.warehouse_index].name;
          loader.hide();
        })
        .catch(error => {
          loader.hide();
          console.log(error.message);
        });
    },

    onEditUpdate(updated_row) {
      const app = this;
      // will insert item into arr at the specified index (deleting 0 items first, that is, it's just an insert).
      app.items_in_stock.splice(app.selected_row_index - 1, 1, updated_row);
      app.page.option = 'list'; // return to list of items
    },
    onSave(data) {
      const app = this;
      app.items_in_stock = data.items_in_stock;// .unshift(updated_row);
      app.page.option = 'list';
    },
    confirmDelete(index, props) {
      // this.loader();
      // alert(props.index);
      const app = this;
      const message = 'This delete action cannot be undone. Click OK to confirm';
      if (confirm(message)) {
        deleteItemInStock.destroy(props.row.id, props.row)
          .then(response => {
            app.items_in_stock.splice(index - 1, 1);
            this.$message({
              message: 'Item has been deleted',
              type: 'success',
            });
          })
          .catch(error => {
            console.log(error);
          });
      }
    },
    expiryFlag(date){
      const product_expiry_date_alert = this.product_expiry_date_alert_in_months;
      const min_expiration = parseInt(product_expiry_date_alert * 30 * 24 * 60 * 60 * 1000); // we use 30 days for one month to calculate
      const today = parseInt(this.moment().valueOf()); // Unix Timestamp (miliseconds) 1.6.0+
      if (parseInt(date) - today <= min_expiration) {
        // console.log(parseInt(date) - today);
        return 'danger'; // flag expiry date as red
      }
      return 'success'; // flag expiry date as green
    },
    formatPackageType(type){
      // var formated_type = type + 's';
      // if (type === 'Box') {
      //   formated_type = type + 'es';
      // }
      return type;
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['List of Products ' + this.in_warehouse, '', '', '', '', '', /* '', */ '', '', '', '', '', '']];
        const tHeader = ['Confirmed By', 'Product', 'Stocked By', 'Batch No.', /* 'Expires', */ 'Quantity', 'In Transit', 'Supplied', 'Physical Stock', 'Reserved for Supply', 'Main Balance', 'Created'];
        const filterVal = ['confirmer.name', 'item.name', 'stocker.name', 'batch_no', 'expiry_date', 'quantity', 'in_transit', 'supplied', 'in_stock', 'reserved_for_supply', 'balance', 'created_at'];
        const list = this.items_in_stock;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: this.filename,
          autoWidth: true,
          bookType: 'csv',
        });
        this.downloadLoading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map(v => filterVal.map(j => {
        if (j === 'confirmer.name') {
          if (v['confirmer'] != null) {
            return v['confirmer']['name'];
          }
        }
        if (j === 'item.name') {
          return v['item']['name'];
        }
        // if (j === 'expiry_date') {
        //   return parseTime(v['expiry_date']);
        // }
        if (j === 'created_at') {
          return parseTime(v['created_at']);
        }
        if (j === 'stocker.name') {
          if (v['stocker'] != null) {
            return v['stocker']['name'];
          }
        }
        if (j === 'quantity') {
          return v['quantity'] + ' ' + v['item']['package_type'];
        }
        if (j === 'in_transit') {
          return v['in_transit'] + ' ' + v['item']['package_type'];
        }
        if (j === 'reserved_for_supply') {
          return v['reserved_for_supply'] + ' ' + v['item']['package_type'];
        }
        if (j === 'supplied') {
          return v['supplied'] + ' ' + v['item']['package_type'];
        }
        if (j === 'in_stock') {
          return v['balance'] + ' ' + v['item']['package_type'];
        }
        if (j === 'balance') {
          return (v['balance'] - v['reserved_for_supply']) + ' ' + v['item']['package_type'];
        }
        return v[j];
      }));
    },
  },
};
</script>
<style rel="stylesheet/scss" lang="scss" scoped>
.alert {
  padding: 5px;
  margin: -5px;
  text-align: right;
}
td {
  padding: 0px !important;
}

</style>
