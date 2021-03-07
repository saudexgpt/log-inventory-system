<template>
  <div class="box">
    <div class="box-header">
      <h4 class="box-title">Add Returned Product</h4>
      <span class="pull-right">
        <a class="btn btn-danger" @click="page.option = 'list'"> Back</a>
      </span>
    </div>
    <div class="box-body">
      <aside>
        <el-form ref="form" :model="form" label-width="120px">
          <el-row :gutter="5" class="padded">
            <el-col :xs="24" :sm="12" :md="12">
              <label for="">Select Site</label>
              <el-select v-model="form.site_id" placeholder="Select Site" filterable class="span">
                <el-option v-for="(site, index) in params.sites" :key="index" :value="site.id" :label="site.name" />

              </el-select>

              <label for="">Select Product</label>
              <el-select v-model="form.item_id" placeholder="Select Product" filterable class="span" @input="fetchProductBatches">
                <el-option v-for="(item, index) in params.items" :key="index" :value="item.id" :label="item.name" />

              </el-select>
              <label for="">Select Batch from where to return</label><br>
              <small>It is displayed as <code>Batch No | Part No | Balance</code></small>
              <el-select v-model="selected_batch" placeholder="Select Batch" filterable class="span">
                <el-option v-for="(batch, batch_index) in batches_of_items_in_stock" :key="batch_index" :value="batch" :label="batch.sub_batch.batch_no + ' | ' + batch.sub_batch.part_number + ' | ' + batch.balance" />

              </el-select>
              <label for="">Quantity</label>
              <el-input v-model="form.quantity" type="text" placeholder="Quantity" class="span" @input="confirmQuantity()" />
            </el-col>
            <el-col :xs="24" :sm="12" :md="12">
              <label for="">Date of Return</label>
              <el-date-picker v-model="form.date_returned" type="date" outline format="yyyy/MM/dd" value-format="yyyy-MM-dd" style="width: 100%" :picker-options="pickerOptions" />
              <label for="">Reason for return</label>
              <el-select v-model="form.reason" placeholder="Select Product" filterable class="span">
                <el-option v-for="(reason, index) in params.product_return_reasons" :key="index" :value="reason" :label="reason" />

              </el-select>
              <div v-if="form.reason === 'Others'">
                <label for="">Specify Other Reasons</label>
                <el-input v-model="form.other_reason" type="text" placeholder="Specify" class="span" />
              </div>
              <upload-image
                v-if="form.site_id !== '' && form.item_id !== ''"
                :site-id="form.site_id"
                :item-id="form.item_id"
                @input="setImageFiles"
              />
              <label
                v-if="show_image_error"
                style="color: red"
              >Please select a product/part and attach an image of it</label>
            </el-col>
          </el-row>
        </el-form>
      </aside>
      <el-row :gutter="2" class="padded">
        <el-col :xs="24" :sm="6" :md="6">
          <el-button type="success" @click="addNewReturnedProduct"><i class="el-icon-upload" />
            Submit
          </el-button>
        </el-col>
      </el-row>
    </div>
  </div>
</template>

<script>
import moment from 'moment';
import UploadImage from './UploadImage';
import Resource from '@/api/resource';
const fetchProductBatchesResources = new Resource('stock/returns/site-product-batches');
const createReturnedProduct = new Resource('stock/returns/store');
export default {
  components: { UploadImage },
  props: {
    params: {
      type: Object,
      default: () => ({}),
    },
    returnedProducts: {
      type: Array,
      default: () => ([]),
    },

    page: {
      type: Object,
      default: () => ({
        option: 'add_new',
      }),
    },

  },
  data() {
    return {
      batches_of_items_in_stock: [],
      pickerOptions: {
        disabledDate(date) {
          var d = new Date(); // today
          d.setDate(d.getDate()); // one year from now
          return date > d;
        },
      },
      fill_fields_error: false,
      form: {
        site_id: '',
        item_id: '',
        site_item_stock_id: '',
        quantity: '',
        date_returned: '',
        reason: null,
        other_reason: null,

      },
      selected_batch: '',
      show_image_error: false,
      image_count: 0,
      uploaded_media: [],
    };
  },
  mounted() {
    this.form.site_id = this.params.sites[0].id;
  },
  methods: {
    moment,
    setImageFiles(fileList) {
      const app = this;
      app.image_count = fileList.length;
      app.show_image_error = false;
      if (app.image_count < 1) {
        app.show_image_error = true;
      }
      app.uploaded_media = fileList;
    },
    fetchProductBatches(){
      const app = this;
      var form = app.form;
      fetchProductBatchesResources.list(form)
        .then(response => {
          app.batches_of_items_in_stock = response.batches_of_items_in_stock;
        });
    },
    confirmQuantity(){
      const app = this;
      const quantity = app.form.quantity;
      const batch = app.selected_batch;
      if (quantity > batch.balance) {
        app.$alert('Quantity MUST NOT be more than batch balance which is: ' + batch.balance);
        app.form.quantity = batch.balance;
      }
      app.form.site_item_stock_id = batch.id;
    },
    addNewReturnedProduct() {
      const app = this;
      var form = app.form;
      app.show_image_error = false;
      console.log(app.image_count);
      if (app.image_count < 1) {
        app.show_image_error = true;
        return false;
      }
      const load = createReturnedProduct.loaderShow();
      form.uploaded_media = app.uploaded_media;
      createReturnedProduct.store(form)
        .then(response => {
          app.resetForm();
          app.$message({ message: 'Returned Products Added Successfully!!!', type: 'success' });
          app.$emit('save', response.returned_product);
          load.hide();
        })
        .catch(error => {
          load.hide();
          alert(error.message);
        });
    },
    resetForm(){
      this.form = {
        warehouse_id: '',
        item_id: '',
        customer_name: '',
        quantity: '',
        batch_no: '',
        expiry_date: '',
        date_returned: '',
        reason: null,
        other_reason: null,
      };
    },

  },
};
</script>

