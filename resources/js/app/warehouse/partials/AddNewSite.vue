<template>
  <div class="box">
    <div class="box-header">
      <h4 class="box-title">Add Site to Warehouse</h4>
      <span class="pull-right">
        <a class="btn btn-danger" @click="page.option = 'list'"> Back</a>
      </span>
    </div>
    <div class="box-body">
      <aside>
        <el-form ref="form" :model="form" label-width="120px">
          <el-row :gutter="5" class="padded">
            <el-col :xs="24" :sm="12" :md="12">
              <el-select v-model="form.warehouse_id" placeholder="Select Warehouse" class="span" @change="fetchSites($event)">
                <el-option v-for="(each_warehouse, index) in warehouses" :key="index" :value="each_warehouse.id" :label="each_warehouse.name" />

              </el-select>
              <p />
              <el-input v-model="form.name" placeholder="Site Name" class="span" />

            </el-col>
            <el-col :xs="24" :sm="12" :md="12">
              <textarea v-model="form.address" placeholder="Site Address" rows="3" class="form-control" />
              <el-form-item label="Status">
                <el-switch v-model="form.enabled" />
              </el-form-item>
              <p />
              <el-form-item label="">
                <el-button type="success" @click="addSite"><i class="el-icon-plus" />
                  Submit
                </el-button>
              </el-form-item>

            </el-col>
          </el-row>

        </el-form>
      </aside>

    </div>
  </div>
</template>

<script>

import Resource from '@/api/resource';
const createSite = new Resource('warehouse/site/store');

export default {
  name: 'AddNewSite',

  props: {
    warehouses: {
      type: Array,
      default: () => ([]),
    },
    sites: {
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
      form: {
        warehouse_id: '',
        name: '',
        address: '',
        enabled: true,

      },
      empty_form: {
        warehouse_id: '',
        name: '',
        address: '',
        enabled: true,

      },

    };
  },

  methods: {

    addSite() {
      const app = this;
      const load = createSite.loaderShow();
      createSite.store(app.form)
        .then(response => {
          app.$message({ message: 'New Warehouse Added Successfully!!!', type: 'success' });
          // app.sites.push(response.site);
          app.form = app.empty_form;
          app.$emit('save', response.site);

          load.hide();
        })
        .catch(error => {
          load.hide();
          alert(error.message);
        });
    },

  },
};
</script>

