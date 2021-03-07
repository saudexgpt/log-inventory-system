<template>
  <div class="app-container">
    <!-- <item-details v-if="page.option== 'view_details'" :item-in-stock="returnedProduct" :page="page" /> -->
    <add-new-returns v-if="page.option== 'add_new'" :returned-products="returned_products" :params="params" :page="page" @save="onSaveUpdate" />

    <edit-returns v-if="page.option== 'edit_returns'" :returned-product="returnedProduct" :params="params" :page="page" @update="onEditUpdate" />
    <div v-if="page.option=='list'" class="box">
      <div class="box-header">
        <h4 class="box-title">List of Returned Products {{ in_site }}</h4>

        <span class="pull-right">
          <a v-if="checkPermission(['manage returned products'])" class="btn btn-info" @click="page.option = 'add_new'"> Add New</a>
        </span>

      </div>
      <div class="box-body">
        <el-col :xs="24" :sm="12" :md="12">
          <label for="">Select Site</label>
          <el-select v-model="form.site_index" placeholder="Select Site" class="span" filterable @input="fetchReturnedProducts">
            <el-option v-for="(site, index) in sites" :key="index" :value="index" :label="site.name" />

          </el-select>

        </el-col>
        <br><br><br><br>
        <v-client-table v-model="returned_products" :columns="columns" :options="options">
          <div slot="quantity" slot-scope="{row}" class="alert alert-warning">
            <!-- {{ row.quantity }} -->
            {{ row.quantity }} {{ row.item.package_type }}

          </div>
          <div slot="quantity_approved" slot-scope="{row}" class="alert alert-info">
            <!-- {{ row.quantity_approved }} -->
            {{ row.quantity_approved }} {{ row.item.package_type }}

          </div>
          <div slot="created_at" slot-scope="{row}">
            {{ moment(row.created_at).calendar() }}

          </div>
          <div slot="confirmer.name" slot-scope="{row}">
            <div :id="row.id">
              <!-- <div v-if="row.confirmed_by == null">
                <a v-if="checkPermission(['audit confirm actions']) && row.stocked_by !== userId" class="btn btn-success" title="Click to confirm" @click="confirmReturnedItem(row.id);"><i class="fa fa-check" /> </a>
              </div>
              <div v-else>
                {{ row.confirmer.name }}
              </div> -->
              {{ (row.confirmer) ? row.confirmer.name : 'Pending Approval' }}
            </div>
          </div>
          <div slot="action" slot-scope="props">
            <span>
              <!-- <a v-if="checkPermission(['manage returned products'])" class="btn btn-primary" @click="returnedProduct=props.row; selected_row_index=props.index; page.option = 'edit_returns'"><i class="fa fa-edit" /> </a> -->

              <a v-if="checkPermission(['approve returned products']) && parseInt(props.row.quantity) > parseInt(props.row.quantity_approved)" class="btn btn-default" @click="openDialog(props.row, props.index)"><i class="fa fa-check" /> </a>

              <a v-if="checkPermission(['manage returned products']) && parseInt(props.row.quantity_approved) < 1" class="btn btn-danger" @click="deleteReturns(props.row.id, props.index)"><i class="fa fa-trash" /> </a>
            </span>
          </div>

        </v-client-table>

      </div>
      <el-dialog
        title="Enter Quantity for Approval"
        :visible.sync="dialogVisible"
        width="20%"
      >
        <el-input v-model="approvalForm.approved_quantity" type="number" placeholder="Enter quantity for approval" />
        <span slot="footer" class="dialog-footer">
          <el-button round @click="dialogVisible = false; approvalForm.approved_quantity = null">Cancel</el-button>
          <el-button round type="primary" @click="approveProduct(); ">Approve</el-button>
        </span>
      </el-dialog>

    </div>

  </div>
</template>
<script>
import { mapGetters } from 'vuex';
import moment from 'moment';
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';

import AddNewReturns from './partials/AddNewReturns';
import EditReturns from './partials/EditReturns';
import Resource from '@/api/resource';
// import Vue from 'vue';
const necessaryParams = new Resource('fetch-necessary-params');
// const fetchWarehouse = new Resource('site/fetch-site');
const returnedProducts = new Resource('stock/returns');
const approveReturnedProducts = new Resource('stock/returns/approve-products');
const deleteReturns = new Resource('stock/returns/delete');
const confirmItemReturned = new Resource('audit/confirm/returned-products');
export default {
  components: { AddNewReturns, EditReturns },
  data() {
    return {
      dialogVisible: false,
      sites: [],
      returned_products: [],
      columns: ['action', 'confirmer.name', 'stocker.name', 'item.name', 'stock.sub_batch.batch_no', 'stock.sub_batch.product_number', 'quantity', 'quantity_approved', 'reason', 'date_returned'],

      options: {
        headings: {
          'confirmer.name': 'Approved By',
          'stocker.name': 'Stocked By',
          'item.name': 'Product',
          'stock.sub_batch.batch_no': 'Batch No.',
          'stock.sub_batch.product_number': 'Product No.',
          quantity: 'QTY',
          quantity_approved: 'QTY Approved',
          date_returned: 'Date Returned',

          // id: 'S/N',
        },
        pagination: {
          dropdown: true,
          chunk: 20,
        },
        filterByColumn: true,
        texts: {
          filter: 'Search:',
        },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['date_returned'],
        filterable: ['item.name', 'stock.sub_batch.batch_no', 'stock.sub_batch.product_number', 'date_returned'],
      },
      page: {
        option: 'list',
      },
      params: {},
      form: {
        site_index: '',
        site_id: '',
      },
      in_site: '',
      returnedProduct: {},
      selected_row_index: '',
      approvalForm: {
        approved_quantity: null,
        product_details: '',
      },
      product_expiry_date_alert_in_months: 9, // defaults to 9 months

    };
  },
  computed: {
    ...mapGetters([
      'userId',
    ]),
  },
  mounted() {
    // this.getWarehouse();
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
          app.sites = response.params.sites;
          if (app.sites.length > 0) {
            app.form.site_id = app.sites[0];
            app.form.site_index = 0;
            app.fetchReturnedProducts();
          }
        });
    },
    confirmReturnedItem(id) {
      // const app = this;
      const form = { id: id };
      const message = 'Click okay to confirm action';
      if (confirm(message)) {
        confirmItemReturned.update(id, form)
          .then(response => {
            if (response.confirmed === 'success'){
              document.getElementById(id).innerHTML = response.confirmed_by;
            }
          });
      }
    },
    fetchReturnedProducts() {
      const app = this;
      const loader = returnedProducts.loaderShow();

      const param = app.form;
      param.site_id = app.sites[param.site_index].id;
      returnedProducts.list(param)
        .then(response => {
          app.returned_products = response.returned_products;
          app.in_site = 'from ' + app.sites[param.site_index].name;
          loader.hide();
        })
        .catch(error => {
          loader.hide();
          console.log(error.message);
        });
    },

    onSaveUpdate(new_row) {
      const app = this;
      // app.returned_products.splice(app.returnedProduct.index-1, 1);
      app.returned_products.unshift(new_row);
      app.page.option = 'list';
    },
    onEditUpdate(updated_row) {
      const app = this;
      // app.returned_products.splice(app.returnedProduct.index-1, 1);
      app.returned_products[app.selected_row_index - 1] = updated_row;
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
    confirmDelete(row, index) {
      // this.loader();

      const app = this;
      const message = 'This delete action cannot be undone. Click OK to confirm';
      if (confirm(message)) {
        deleteReturns.destroy(row.id, row)
          .then(response => {
            app.returned_products.splice(index - 1, 1);
            this.$message({
              message: 'Entry has been deleted',
              type: 'success',
            });
          })
          .catch(error => {
            console.log(error);
          });
      }
    },
    openDialog(product, selected_row_index){
      const app = this;
      app.approvalForm.product_details = product;
      app.approvalForm.approved_quantity = product.quantity;
      app.selected_row_index = selected_row_index;
      app.dialogVisible = true;
    },
    approveProduct(){
      const app = this;

      const param = app.approvalForm;
      const balance = parseInt(app.approvalForm.product_details.quantity) - parseInt(app.approvalForm.product_details.quantity_approved);
      if (parseInt(param.approved_quantity) <= balance) {
        if (parseInt(param.approved_quantity) > 0) {
          app.dialogVisible = false;
          const loader = approveReturnedProducts.loaderShow();
          approveReturnedProducts.store(param)
            .then(response => {
              app.returned_products.splice(app.selected_row_index - 1, 1, response.returned_product);
              loader.hide();
            })
            .catch(error => {
              loader.hide();
              console.log(error.message);
            });
        } else {
          app.$alert('Please enter a value greater than zero');
          app.approvalForm.approved_quantity = balance;
          return;
        }
      } else {
        app.$alert('Approved Quantity MUST NOT be greater than ' + balance);
        app.approvalForm.approved_quantity = balance;
        return;
      }
    },
    formatPackageType(type){
      var formated_type = type + 's';
      if (type === 'Box') {
        formated_type = type + 'es';
      }
      return formated_type;
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
