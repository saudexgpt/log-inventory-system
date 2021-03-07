<template>
  <div class="box">
    <div class="box-header">
      <h4 class="box-title">Users assigned to {{ site.name }}</h4>
      <span class="pull-right">
        <a class="btn btn-danger" @click="page.option = 'list'"> Back</a>
      </span>
    </div>
    <div class="box-body">
      <aside>
        <el-form ref="form" :model="form" label-width="120px">
          <el-row :gutter="5">
            <el-col :xs="24" :sm="24" :md="24">
              <label for="">Select Users to assign/remove to/from {{ site.name }}</label><br>
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
      <v-client-table v-model="site.users" :columns="columns" />
    </div>
  </div>
</template>

<script>
import Resource from '@/api/resource';
// import Vue from 'vue';
const assignUsersToSite = new Resource('warehouse/site/add-user-to-site');
const removeUsersFormSite = new Resource('warehouse/site/remove-user-from-site');
export default {
  // name: 'WarehouseDetails',

  props: {
    site: {
      type: Object,
      default: () => ({}),
    },
    page: {
      type: Object,
      default: () => ({
        option: 'site_details',
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
        site_id: this.site.id,
        user_ids: [],

      },

    };
  },
  mounted() {
    this.form.site_id = this.site.id;
    if (this.form.enabled === 1) {
      this.form.enabled = true;
    } else {
      this.form.enabled = false;
    }
  },
  methods: {

    assignUsers() {
      const app = this;
      assignUsersToSite.store(app.form)
        .then(response => {
          app.$message({ message: 'Users Assigned Successfully!!!', type: 'success' });
          app.site.users = response.site_users;
        })
        .catch(error => {
          alert(error.message);
        });
    },
    removeUsers() {
      const app = this;
      removeUsersFormSite.store(app.form)
        .then(response => {
          app.$message({ message: 'Users Removed Successfully!!!', type: 'success' });
          app.site.users = response.site_users;
        })
        .catch(error => {
          alert(error.message);
        });
    },

  },
};
</script>

