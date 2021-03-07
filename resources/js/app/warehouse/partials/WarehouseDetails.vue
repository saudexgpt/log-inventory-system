<template>
  <div class="box">
    <div class="box-header">
      <h4 class="box-title">Staff assigned to {{ warehouse.name }}</h4>
      <span class="pull-right">
        <a class="btn btn-danger" @click="page.option = 'list'"> Back</a>
      </span>
    </div>
    <div class="box-body">
      <aside>
        <el-form ref="form" :model="form" label-width="120px">
          <el-row :gutter="5">
            <el-col :xs="24" :sm="24" :md="24">
              <label for="">Select Users to assign/remove to/from {{ warehouse.name }}</label><br>
              <el-select v-model="form.user_ids" multiple="true">
                <el-option v-for="(user, index) in users" :key="index" :value="user.id" :label="user.name" />
              </el-select>
              <el-button type="success" @click="assignUsers"><i class="el-icon-check" />
                Assign
              </el-button>
              <el-button type="danger" @click="removeUsers"><i class="el-icon-close" />
                Remove
              </el-button>
            </el-col>
          </el-row>
        </el-form>
      </aside>
      <legend>Assigned Users</legend>
      <v-client-table v-model="warehouse.users" :columns="columns" />
    </div>
  </div>
</template>

<script>
import Resource from '@/api/resource';
// import Vue from 'vue';
const assignUsersToWarehouse = new Resource('warehouse/add-user-to-warehouse');
const removeUsersFormWarehouse = new Resource('warehouse/remove-user-from-warehouse');

export default {
  // name: 'WarehouseDetails',

  props: {
    warehouses: {
      type: Array,
      default: () => ([]),
    },
    warehouse: {
      type: Object,
      default: () => ({}),
    },
    page: {
      type: Object,
      default: () => ({
        option: 'warehouse_details',
      }),
    },
    users: {
      type: Array,
      default: () => ([]),
    },

  },
  data() {
    return {
      columns: ['name', 'email', 'phone', 'address'],

      form: {
        warehouse_id: this.warehouse.id,
        user_ids: [],

      },

    };
  },
  mounted() {
    this.form.warehouse_id = this.warehouse.id;
    if (this.form.enabled === 1) {
      this.form.enabled = true;
    } else {
      this.form.enabled = false;
    }
  },
  methods: {

    assignUsers() {
      const app = this;
      assignUsersToWarehouse.store(app.form)
        .then(response => {
          app.$message({ message: 'Users Assigned Successfully!!!', type: 'success' });
          app.warehouse.users = response.warehouse_users;
        })
        .catch(error => {
          alert(error.message);
        });
    },
    removeUsers() {
      const app = this;
      removeUsersFormWarehouse.store(app.form)
        .then(response => {
          app.$message({ message: 'Users Removed Successfully!!!', type: 'success' });
          app.warehouse.users = response.warehouse_users;
        })
        .catch(error => {
          alert(error.message);
        });
    },

  },
};
</script>

