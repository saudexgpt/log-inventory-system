<template>
  <div class="app-container">
    <div v-if="page.option==='list'">
      <el-dialog
        :title="'Add Delivery Cost'"
        :visible.sync="dialogFormVisible"
        :before-close="closeModal"
      >
        <el-row :gutter="10">
          <el-col :xs="24" :sm="12" :md="12">
            <label for>Supplying Warehouse</label>
            <el-select
              v-model="form.warehouse_index"
              placeholder="Select Warehouse"
              class="span"
              filterable
              @input="getWaybills"
            >
              <el-option
                v-for="(warehouse, index) in warehouses"
                :key="index"
                :value="index"
                :label="warehouse.name"
              />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="12" :md="12">
            <label for>Dispatch Company</label>
            <el-select
              v-model="new_waybill_expense.dispatch_company"
              placeholder="Select"
              filterable
              class="span"
            >
              <el-option
                v-for="(dispatch_company, com_index) in params.dispatch_companies"
                :key="com_index"
                :value="dispatch_company"
                :label="dispatch_company"
              />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="12" :md="12">
            <label for>Select Pending Customer Waybill No.</label>
            <el-select
              v-model="new_waybill_expense.waybill_ids"
              placeholder="Select Customer Waybill"
              class="span"
              multiple
              filterable
            >
              <el-option
                v-for="(waybill, waybill_index) in waybills_with_pending_wayfare"
                :key="waybill_index"
                :value="waybill.id"
                :label="waybill.waybill_no"
              />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="12" :md="12">
            <label for>Select Pending Inter-Warehouse Waybill No.</label>
            <el-select
              v-model="new_waybill_expense.transfer_waybill_ids"
              placeholder="Select Inter-Warehouse Waybill"
              class="span"
              multiple
              filterable
            >
              <el-option
                v-for="(transfer_waybill, tnx_waybill_index) in transfer_waybills_with_pending_wayfare"
                :key="tnx_waybill_index"
                :value="transfer_waybill.id"
                :label="transfer_waybill.transfer_request_waybill_no"
              />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="12" :md="12">
            <label for>Trip No.</label>
            <el-input v-model="new_waybill_expense.trip_no" class="span" disabled />
          </el-col>
          <div v-if="new_waybill_expense.dispatch_company === 'AGGREKO LOGISTICS'">
            <el-col :xs="24" :sm="12" :md="12">
              <label for>Select Vehicle</label>
              <el-select
                v-model="selected_vehicle"
                placeholder="Select Vehicle"
                class="span"
                filterable
                @input="setVehicleDetails"
              >
                <el-option
                  v-for="(vehicle, vehicle_index) in vehicles"
                  :key="vehicle_index"
                  :value="vehicle_index"
                  :label="vehicle.plate_no"
                />
              </el-select>
            </el-col>
          </div>
          <div v-else>
            <el-col :xs="24" :sm="12" :md="12">
              <label for>Vehicle No.</label>
              <el-input v-model="new_waybill_expense.vehicle_no" placeholder="Enter vehicle no." />
            </el-col>
            <el-col :xs="24" :sm="12" :md="12">
              <label for>Dispatcher Name(s)</label>
              <el-input
                v-model="new_waybill_expense.dispatchers"
                type="textarea"
                placeholder="Enter dispatchers name"
              />
            </el-col>
          </div>

          <el-col
            v-if="new_waybill_expense.dispatch_company === 'FOB (Free On Board)'"
            :xs="24"
            :sm="24"
            :md="24"
          >
            <label for>Enter Amount</label>
            <el-input v-model="new_waybill_expense.amount" min="0" type="number" disabled />
          </el-col>
          <el-col v-else :xs="24" :sm="12" :md="12">
            <label for>Enter Amount</label>
            <el-input v-model="new_waybill_expense.amount" min="0" type="number" />
          </el-col>
          <el-col :xs="24" :sm="12" :md="12">
            <label for>Extra Description</label>
            <el-input
              v-model="new_waybill_expense.description"
              type="textarea"
              :row="5"
              placeholder="Give extra description about destinations of trip, vehicle details if not to be delivered by AGGREKO LOGISTICS"
              class="span"
            />
            <p>&nbsp;</p>
            <el-button
              type="success"
              :disabled="new_waybill_expense.description === ''"
              @click="addDeliveryCost"
            >
              <i class="el-icon-plus" />
              Submit
            </el-button>
          </el-col>
        </el-row>
      </el-dialog>
      <div class="col-md-12">
        <div class="box">
          <div class="box-header">
            <h4 class="box-title">{{ tableTitle }}</h4>
            <span class="pull-right">
              <a class="btn btn-info" @click="dialogFormVisible=true"> Add Delivery Cost</a>
            </span>

          </div>
          <div class="box-body">
            <el-row :gutter="10">
              <el-col :xs="24" :sm="12" :md="12">
                <label for>Select Warehouse</label>
                <el-select
                  v-model="form.warehouse_index"
                  placeholder="Select Warehouse"
                  class="span"
                  filterable
                  @input="getWaybills"
                >
                  <el-option
                    v-for="(warehouse, index) in warehouses"
                    :key="index"
                    :value="index"
                    :label="warehouse.name"
                  />
                </el-select>
              </el-col>
              <el-col :xs="24" :sm="12" :md="12">
                <!-- <label class="radio-label" style="padding-left:0;">Filename: </label>
                <el-input v-model="filename" :placeholder="$t('excel.placeholder')" style="width:340px;" prefix-icon="el-icon-document" />-->
                <el-button
                  :loading="downloadLoading"
                  style="margin:0 0 20px 20px;"
                  type="primary"
                  icon="document"
                  @click="handleDownload"
                >Export Excel</el-button>
              </el-col>
            </el-row>
            <p>&nbsp;</p>
            <v-client-table v-model="delivery_trips" :columns="columns" :options="options">
              <div slot="child_row" slot-scope="props">
                <aside>
                  <div class="pull-right">
                    Add Waybill to Trip
                    <el-select
                      v-model="extra_waybill_id"
                      placeholder="Select Customer Waybill"
                      class="span"
                      filterable
                    >
                      <el-option
                        v-for="(waybill, waybill_index) in waybills_with_pending_wayfare"
                        :key="waybill_index"
                        :value="waybill.id"
                        :label="waybill.waybill_no"
                      />
                    </el-select>
                    <el-select
                      v-model="extra_transfer_waybill_id"
                      placeholder="Select Inter-Warehouse Waybill"
                      class="span"
                      filterable
                    >
                      <el-option
                        v-for="(transfer_waybill, txn_waybill_index) in transfer_waybills_with_pending_wayfare"
                        :key="txn_waybill_index"
                        :value="transfer_waybill.id"
                        :label="transfer_waybill.transfer_request_waybill_no"
                      />
                    </el-select>
                    <el-button
                      type="success"
                      icon="el-icon-plus"
                      @click="addWaybillToTrip(props.row.id, props.index)"
                    />
                  </div>
                  <div>Waybills For Trip No.: {{ props.row.trip_no }}</div>
                  <table class="table table-bordered table-striped">
                    <thead>
                      <tr>
                        <th>Action</th>
                        <th>Waybill Number</th>
                        <th>Created at</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(waybill, waybill_index) in props.row.waybills" :key="waybill_index">
                        <td>
                          <a
                            v-if="waybill.status === 'pending'"
                            class="btn btn-danger"
                            @click="removeWaybill(waybill.id, props.row.id, props.index)"
                          >
                            <i class="el-icon-delete" />
                          </a>
                        </td>
                        <td>
                          {{ waybill.waybill_no }}
                        </td>
                        <td>{{ moment(waybill.created_at).fromNow() }}</td>
                      </tr>
                      <tr v-for="(transfer_waybill, txn_waybill_index) in props.row.transfer_waybills" :key="txn_waybill_index">
                        <td>
                          <a
                            v-if="transfer_waybill.status === 'pending'"
                            class="btn btn-danger"
                            @click="removeTransferWaybill(transfer_waybill.id, props.row.id, props.index)"
                          >
                            <i class="el-icon-delete" />
                          </a>
                        </td>
                        <td>
                          {{ transfer_waybill.transfer_request_waybill_no }}
                        </td>
                        <td>{{ moment(transfer_waybill.created_at).fromNow() }}</td>
                      </tr>
                    </tbody>
                  </table>

                  <!-- <v-client-table
                    v-model="props.row.waybills"
                    :columns="['action', 'waybill_no', 'created_at']"
                  >
                    <div slot="created_at" slot-scope="{row}">{{ moment(row.created_at).fromNow() }}</div>
                    <div slot="action" slot-scope="{row}">
                      <a
                        v-if="row.status === 'pending'"
                        class="btn btn-danger"
                        @click="removeWaybill(row.id, props.row.id, props.index)"
                      >
                        <i class="el-icon-delete" />
                      </a>
                    </div>
                  </v-client-table> -->
                </aside>
              </div>
              <div slot="waybills" slot-scope="props">
                <div v-if="props.row.waybills.length > 0">
                  <div v-for="(waybill, index) in props.row.waybills" :key="index">
                    {{ waybill.waybill_no }},
                  </div>
                </div>
                <div v-if="props.row.transfer_waybills.length > 0">
                  <div v-for="(transfer_waybill, transfer_index) in props.row.transfer_waybills" :key="transfer_index">
                    {{ transfer_waybill.transfer_request_waybill_no }},
                  </div>
                </div>

              </div>
              <div slot="action" slot-scope="props">
                <div v-if="props.row.cost.confirmed_by !== null">
                  <el-button type="success" @click="sendWaybillsOnTransit(props.row);">Send On Transit</el-button>
                </div>

              </div>
              <div
                slot="amount"
                slot-scope="props"
              >{{ currency + Number(props.row.cost.amount).toLocaleString() }}</div>
              <div
                slot="created_at"
                slot-scope="props"
              >{{ moment(props.row.created_at).format('MMMM Do YYYY, h:mm:ss a') }}</div>
              <div
                slot="updated_at"
                slot-scope="props"
              >{{ moment(props.row.updated_at).format('MMMM Do YYYY, h:mm:ss a') }}</div>
              <div slot="confirmer.name" slot-scope="props">
                <div :id="props.row.cost.id">
                  <div v-if="props.row.cost.confirmed_by == null">
                    <a
                      v-if="checkPermission(['audit confirm actions'])"
                      title="Click to confirm"
                      class="btn btn-warning"
                      @click="confirmDeliveryCost(props.index, props.row.cost.id);"
                    >
                      <i class="fa fa-check" />
                    </a>
                  </div>
                  <div v-else>{{ props.row.cost.confirmer.name }}</div>
                </div>
              </div>
            </v-client-table>
          </div>
        </div>
      </div>
    </div>
    <!-- <div v-if="page.option==='waybill_details'">
      <a class="btn btn-danger no-print" @click="page.option='list'">Go Back</a>
      <waybill-details :waybill="waybill" :page="page" :company-name="params.company_name" :company-contact="params.company_contact" :currency="currency" />
    </div>-->
  </div>
</template>
<script>
import moment from 'moment';
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';
import { parseTime } from '@/utils';
import Resource from '@/api/resource';
// import WaybillDetails from './partials/WaybillDetails';
const necessaryParams = new Resource('fetch-necessary-params');
const fetchWaybillExpenses = new Resource('invoice/waybill/expenses');
const addWaybillExpenses = new Resource('invoice/waybill/add-waybill-expenses');
const detachWaybillFromTrip = new Resource('invoice/waybill/detach-waybill-from-trip');
const addWaybillToTripResource = new Resource('invoice/waybill/add-waybill-to-trip');
const confirmDeliveryCostResource = new Resource('audit/confirm/delivery-cost');
const changeTransferWaybillStatusResource = new Resource('transfers/waybill/send-bulk-on-transit');
const changeWaybillStatusResource = new Resource('invoice/waybill/send-bulk-on-transit');
// const deleteItemInStock = new Resource('stock/items-in-stock/delete');
export default {
  // name: 'WaybillDeliveryCost',
  // components: { WaybillDetails },
  props: {
    canGenerateNewWaybill: {
      type: Boolean,
      default: () => true,
    },
  },
  data() {
    return {
      warehouses: [],
      vehicles: [],
      delivery_trips: [],
      waybills_with_pending_wayfare: [],
      transfer_waybills_with_pending_wayfare: [],
      currency: '',
      columns: [
        'action',
        'confirmer.name',
        'dispatch_company',
        'trip_no',
        'waybills',
        'amount',
        'description',
        'vehicle_no',
        'dispatchers',
        'created_at',
      ],

      options: {
        headings: {
          trip_no: 'Trip No.',
          'waybills': 'Waybill Numbers',
          'confirmer.name': 'Confirmed By',

          // id: 'S/N',
        },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['trip_no', 'amount', 'created_at', 'updated_at'],
        filterable: ['trip_no', 'amount', 'created_at', 'updated_at'],
      },
      page: {
        option: 'list',
      },
      params: {},
      form: {
        warehouse_index: '',
        warehouse_id: '',
      },
      new_waybill_expense: {
        dispatch_company: 'AGGREKO LOGISTICS',
        waybill_ids: [],
        transfer_waybill_ids: [],
        vehicle_id: null,
        amount: 0,
        description: '',
        warehouse_id: '',
        trip_no: '',
        dispatchers: '',
      },
      tableTitle: '',
      selected_row_index: '',
      selected_vehicle: null,
      // submitTitle: 'Fetch',
      // panel: 'month',
      // future: false,
      // panels: ['range', 'week', 'month', 'quarter', 'year'],
      // show_calendar: false,
      downloadLoading: false,
      filename: 'Delivery Cost',
      extra_waybill_id: null,
      extra_transfer_waybill_id: null,
      dialogFormVisible: true,
    };
  },

  created() {
    this.fetchNecessaryParams();
  },
  beforeDestroy() {},
  methods: {
    moment,
    checkPermission,
    checkRole,
    showCalendar() {
      this.show_calendar = !this.show_calendar;
    },
    closeModal() {
      this.dialogFormVisible = false;
    },
    fetchNecessaryParams() {
      const app = this;
      necessaryParams.list().then((response) => {
        app.params = response.params;
        app.warehouses = response.params.warehouses;
        app.currency = response.params.currency;
        if (app.warehouses.length > 0) {
          app.form.warehouse_id = app.warehouses[0];
          app.form.warehouse_index = 0;
          app.getWaybills();
        }
      });
    },
    setVehicleDetails() {
      const app = this;
      const selected_vehicle = app.selected_vehicle;
      app.new_waybill_expense.vehicle_id = app.vehicles[selected_vehicle].id;
      app.new_waybill_expense.vehicle_no =
        app.vehicles[selected_vehicle].plate_no;
      const vehicle_drivers = app.vehicles[selected_vehicle].vehicle_drivers;
      var drivers = '';
      vehicle_drivers.forEach((vehicle_driver) => {
        drivers += vehicle_driver.driver.user.name + ',';
      });
      app.new_waybill_expense.dispatchers = drivers;
    },
    format(date) {
      var month = date.toLocaleString('en-US', { month: 'short' });
      return month + ' ' + date.getDate() + ', ' + date.getFullYear();
    },
    setDateRange(values) {
      const app = this;
      document.getElementById('pick_date').click();
      app.show_calendar = false;
      let panel = app.panel;
      let from = app.week_start;
      let to = app.week_end;
      if (values !== '') {
        to = this.format(new Date(values.to));
        from = this.format(new Date(values.from));
        panel = values.panel;
      }
      app.form.from = from;
      app.form.to = to;
      app.form.panel = panel;
      app.getWaybills();
    },
    getWaybills() {
      const app = this;
      app.new_waybill_expense.warehouse_id =
        app.warehouses[app.form.warehouse_index].id;
      const loader = fetchWaybillExpenses.loaderShow();
      const param = app.form;
      param.warehouse_id = app.warehouses[param.warehouse_index].id;
      app.tableTitle =
        'Waybill Delivery Expenses in ' +
        app.warehouses[param.warehouse_index].name;
      fetchWaybillExpenses
        .list(param)
        .then((response) => {
          app.delivery_trips = response.delivery_trips;
          app.waybills_with_pending_wayfare = response.waybills_with_pending_wayfare;
          app.transfer_waybills_with_pending_wayfare = response.transfer_waybills_with_pending_wayfare;
          app.new_waybill_expense.trip_no = response.trip_no;
          app.vehicles = response.vehicles;
          loader.hide();
        })
        .catch((error) => {
          loader.hide();
          console.log(error.message);
        });
    },
    confirmDeliveryCost(index, delivery_cost_id) {
      const app = this;
      const form = { id: delivery_cost_id };
      const message = 'Click okay to confirm action';
      if (confirm(message)) {
        confirmDeliveryCostResource
          .update(delivery_cost_id, form)
          .then((response) => {
            if (response.confirmed === 'success') {
              document.getElementById(delivery_cost_id).innerHTML = response.confirmed_by;
              app.delivery_trips[index - 1].cost.confirmed_by = response.confirmed_by;
            }
          });
      }
    },
    addDeliveryCost() {
      const app = this;
      const loader = addWaybillExpenses.loaderShow();

      const param = app.new_waybill_expense;
      addWaybillExpenses
        .store(param)
        .then((response) => {
          this.$message({
            type: 'success',
            message: 'Delivery Cost added successfully',
          });
          app.resetForm();
          app.delivery_trips.push(response.delivery_trip);
          app.waybills_with_pending_wayfare = response.waybills_with_pending_wayfare;
          app.transfer_waybills_with_pending_wayfare = response.transfer_waybills_with_pending_wayfare;
          app.new_waybill_expense.trip_no = response.trip_no;
          loader.hide();
        })
        .catch((error) => {
          loader.hide();
          console.log(error.message);
        });
    },
    addWaybillToTrip(delivery_trip_id, index) {
      const app = this;
      const param = {
        waybill_id: app.extra_waybill_id,
        transfer_waybill_id: app.extra_transfer_waybill_id,
        delivery_trip_id: delivery_trip_id,
      };
      if (app.extra_waybill_id !== null || app.extra_transfer_waybill_id !== null) {
        addWaybillToTripResource.store(param).then((response) => {
          app.delivery_trips[index - 1].waybills = response.delivery_trip.waybills;
          app.delivery_trips[index - 1].transfer_waybills = response.delivery_trip.transfer_waybills;
          app.waybills_with_pending_wayfare = response.waybills_with_pending_wayfare;
          app.transfer_waybills_with_pending_wayfare = response.transfer_waybills_with_pending_wayfare;
          app.new_waybill_expense.trip_no = response.trip_no;

          app.extra_waybill_id = null; // return to initial value
          app.extra_transfer_waybill_id = null; // return to initial value
        });
      } else {
        this.$alert('Please select a waybill to add', 'Nothing Selected', {
          confirmButtonText: 'OK',
          callback: () => {},
        });
      }
    },
    removeWaybill(waybill_id, delivery_trip_id, index) {
      const app = this;
      app
        .$confirm(
          'Do you really want to remove this waybill from this trip?',
          'Please Confirm',
          {
            confirmButtonText: 'OK',
            cancelButtonText: 'Cancel',
            type: 'warning',
          }
        )
        .then(() => {
          const param = {
            waybill_id: waybill_id,
            delivery_trip_id: delivery_trip_id,
          };
          detachWaybillFromTrip.store(param).then((response) => {
            app.delivery_trips[index - 1].waybills =
              response.delivery_trip.waybills;
            app.waybills_with_pending_wayfare =
              response.waybills_with_pending_wayfare;
            app.new_waybill_expense.trip_no = response.trip_no;
          });
        })
        .catch(() => {
          this.$message({
            type: 'info',
            message: 'Action canceled',
          });
        });
    },
    removeTransferWaybill(waybill_id, delivery_trip_id, index){
      const app = this;
      app
        .$confirm(
          'Do you really want to remove this waybill from this trip?',
          'Please Confirm',
          {
            confirmButtonText: 'OK',
            cancelButtonText: 'Cancel',
            type: 'warning',
          }
        )
        .then(() => {
          const param = {
            transfer_waybill_id: waybill_id,
            delivery_trip_id: delivery_trip_id,
          };
          detachWaybillFromTrip.store(param).then((response) => {
            app.delivery_trips[index - 1].transfer_waybills = response.delivery_trip.transfer_waybills;
            app.waybills_with_pending_wayfare = response.waybills_with_pending_wayfare;
            app.transfer_waybills_with_pending_wayfare = response.transfer_waybills_with_pending_wayfare;
            app.new_waybill_expense.trip_no = response.trip_no;
          });
        })
        .catch(() => {
          this.$message({
            type: 'info',
            message: 'Action canceled',
          });
        });
    },
    resetForm() {
      this.new_waybill_expense = {
        dispatch_company: 'AGGREKO LOGISTICS',
        waybill_ids: [],
        transfer_waybill_ids: [],
        vehicle_id: null,
        amount: 0,
        description: '',
        trip_no: '',
        dispatchers: '',
      };
    },
    sendWaybillsOnTransit(delivery_trip){
      const app = this;
      const message = "Trip's pending waybills will be sent ON TRANSIT. Click OK to confirm";
      if (confirm(message)) {
        var transfer_waybill_ids = [];
        var waybill_ids = [];
        if (delivery_trip.waybills.length > 0) {
          delivery_trip.waybills.forEach((waybill) => {
            waybill_ids.push(waybill.id);
          });
          const param = {
            waybill_ids: waybill_ids,
            status: 'in transit',
          };
          changeWaybillStatusResource.store(param).then((response) => {});
        }
        if (delivery_trip.transfer_waybills.length > 0) {
          delivery_trip.transfer_waybills.forEach((transfer_waybill) => {
            transfer_waybill_ids.push(transfer_waybill.id);
          });
          const param = {
            waybill_ids: transfer_waybill_ids,
            status: 'in transit',
          };
          changeTransferWaybillStatusResource.store(param).then((response) => {});
        }
        app.$message('Action Successful');
      }
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then((excel) => {
        const multiHeader = [[this.tableTitle, '', '', '', '', '', '']];
        const tHeader = [
          'WAYBILL NUMBER',
          'AMOUNT',
          'NOTE',
          'DATE',
          'DISPATCHERS',
        ];
        const filterVal = [
          'waybill.waybill_no',
          'amount',
          'description',
          'created_at',
          'dispatchers',
        ];
        const list = this.delivery_trips;
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
      return jsonData.map((v) =>
        filterVal.map((j) => {
          if (j === 'created_at') {
            return parseTime(v[j]);
          }
          if (j === 'waybill.waybill_no') {
            return v['waybill']['waybill_no'];
          }
          if (j === 'dispatchers') {
            var vehicle_drivers =
              v['waybill']['dispatcher']['vehicle']['vehicle_drivers'];
            var drivers = '';
            vehicle_drivers.forEach((element) => {
              var name = element.driver.user.name;
              drivers += name + ', ';
            });
            return drivers;
          }
          return v[j];
        })
      );
    },
  },
};
</script>
