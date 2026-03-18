```html
<template>
    <div class="input-group date" :name="name" :id="name" data-target-input="nearest">
        <slot>
            <input type="text" class="form-control datetimepicker-input" :data-target="`#${name}`" :value="value" data-input>
        </slot>
        <div class="input-group-append" :data-target="`#${name}`" data-toggle="datetimepicker">
            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
        </div>
    </div>
</template>
```
<script>
import DateTimePicker from 'tempusdominus-bootstrap-4';

export default {
		props: {
			name: String,
			value: String
		},
		data () {
			return {
				datepicker: null
			}
		},
		mounted () {
			var this_this = this;
			var element = this.$el.getElementsByTagName('input')[0];
			this.datepicker = new DateTimePicker(
				element, {
					format: 'Y-m-d',
					onChange: function(selectedDates, dateStr, instance) {
						this_this.$emit('onChange', dateStr)
					},
				});
		},
        methods: {
            clear() {
                this.datepicker.clear();
            }
        }
	};
</script>
