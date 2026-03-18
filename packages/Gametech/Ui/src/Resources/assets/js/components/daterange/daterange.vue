<template>
    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="far fa-clock"></i></span>
        </div>
        <input type="text" class="form-control form-control-sm float-right" id="search_date">
        <input type="hidden" class="form-control float-right" id="startDate"
               name="startDate" v-model="startDate">
        <input type="hidden" class="form-control float-right" id="endDate" name="endDate" v-model="endDate">
    </div>
</template>

<script>
require('./daterangepicker.js');
import './daterangepicker.css';
import moment from "moment";

export default {

        data() {
            return {
                datepicker: {},
                startDate: String,
                endDate: String,
                elementHandle : 'search_date'
            };
        },
        mounted() {

            this.create();

            this.startDate = moment().startOf('day');
            this.endDate = moment().endOf('day');


            $("#frmsearch").submit(function () {
                window.LaravelDataTables["dataTableBuilder"].draw(true);
            });
        },

        methods: {
           create() {
               this.datepicker = $("#" + this.elementHandle);
               this.datepicker = new DateRangePicker({
                   showDropdowns: true,
                   timePicker: true,
                   timePicker24Hour: true,
                   timePickerSeconds: true,
                   autoApply: true,
                   startDate: moment().startOf('day'),
                   endDate: moment().endOf('day'),
                   locale: {
                       format: 'DD/MM/YYYY HH:mm:ss'
                   },
                   ranges: {
                       'Today': [moment().startOf('day'), moment().endOf('day')],
                       'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                       'Last 7 Days': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                       'Last 30 Days': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
                       'This Month': [moment().startOf('month').startOf('day'), moment().endOf('month').endOf('day')],
                       'Last Month': [moment().subtract(1, 'month').startOf('month').startOf('day'), moment().subtract(1, 'month').endOf('month').endOf('day')]
                   }
               });

               // this.datepicker.on('apply.daterangepicker', function (ev, picker) {
               //     var start = picker.startDate.format('YYYY-MM-DD HH:mm:ss');
               //     var end = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
               //     this.startDate = start;
               //     this.endDate = end;
               //
               // });
           }
        }
    };
</script>
