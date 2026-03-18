import Swal from 'sweetalert2';

import 'tempusdominus-bootstrap-4/build/css/tempusdominus-bootstrap-4.css';
// import 'select2-theme-bootstrap4/dist/select2-bootstrap.css';


const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
})

window.Toast = Toast;


require('daterangepicker');
require('tempusdominus-bootstrap-4');
require('inputmask');


// Vue.component('date-component', require('./components/DateComponent.vue').default);

// Vue.component('date', DateComponent);

