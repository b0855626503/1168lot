<script type="text/x-template" id="contributor-template">
    <div  class="sub-page sub-footer" style="min-height: 100vh;">
        <div class="container" style="max-width: 720px;">
            <div class="mt-3 text-center">
                <h2>@{{ trans('app.con.suggest') }}</h2>
            </div>
            <div class="card bg-dark">
                <div class="card-body">
                    <div class=" card bg-dark-2 mt-2">
                        <div class="card-body p-1">
                            <div class="row g-2">
                                <div class="col-12 text-center">
                                    <div class="card bg-dark py-2">
                                        <div class="small text-muted">@{{ trans('app.con.percent') }}</div>
                                        <div class="text-dark text-warning fs-5 bg-light rounded-pill w-100 mx-auto" style="max-width: 14em;">@{{ faststart }} </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 text-center">
                                    <div class="card bg-dark bg-circle bg-circle-danger">
                                        <div class="small text-muted">@{{ trans('app.con.sum_income') }}</div>
                                        <div class="text-warning fs-5">@{{ profile.payments_promotion_credit_bonus_sum || '0.00' }}</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 text-center">
                                    <div class="card bg-dark bg-circle bg-circle-success">
                                        <div class="small text-muted">@{{ trans('app.con.remain') }}</div>
                                        <div class="text-warning fs-5">@{{ userdata.faststart }}</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 text-center">
                                    <div class="card bg-dark bg-circle bg-circle-info">
                                        <div class="small text-muted">@{{ trans('app.con.count') }}</div>
                                        <div class="text-warning fs-5 fw-bold">@{{ profile.downs_count || 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="theme-form mb-4">
                    <form ref="faststartForm" @submit.prevent="onSubmit">
                        <div class="text-center mt-1 mb-1">
                            <button class="btn btn-primary w-100 rounded-pill btn-custom-primary"
                                    style="max-width: 20em;"
                                    type="submit"
                                    :disabled="isSubmitting">
                                @{{ trans('app.con.get') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card bg-dark mt-2">
                <div class="card-body">
                    <div class="fs-6 fw-light">
                        <div class="w-100 link-ref-coppy">
                            <span class="link-text lh-1">@{{ contributorUrl }}</span>

                            <!-- COPY LINK -->
                            <button type="button"  class="btn_copy_bankcode btn btn-custom btn-custom-primary btn_copy_ref">
                                <i aria-hidden="true" class="fa fa-clone me-1" style="font-size: 22px;"></i> @{{ trans('app.con.copy') }}
                                <!-- COPY THIS -->
                                <b class="d-none">@{{ contributorUrl }}</b>
                                <!-- COPY THIS -->
                            </button>
                            <!-- COPY LINK -->

                            <!---->
                            <input tabindex="-1" aria-hidden="true" class="ip-copyfrom">
                        </div>
                        <hr>
                        @{{ trans('app.con.more', { field: faststart }) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

@push('components')
	
	<script type="module">

        Vue.component('contributor', {
            template: '#contributor-template',
            props: {
                faststart: {
                    type: [Number, String],
                    default: 0
                },
                profile: {
                    type: Object,
                    default: () => ({})
                },
                contributorUrl: { // ไว้สำหรับลิงก์สมัคร
                    type: String,
                    default: ''
                }
            },
            data() {
                return {
                    userdata: @json($userdata),
                    isSubmitting: false
                };
            },
            methods: {

                onSubmit() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;

                    const url = '{{ route('customer.transfer.bonus.confirm') }}';

                    const payload = {
                        id: 'FASTSTART'
                    };

                    axios.post(url, payload)
                        .then(({ data }) => {
                            if (data && data.success) {
                                Swal.fire(
                                    this.trans('app.bonus.success'),
                                    data.message || '',
                                    'success'
                                );
                            } else {
                                Swal.fire(
                                    this.trans('app.bonus.fail'),
                                    (data && data.message) || '',
                                    'error'
                                );
                            }
                        })
                        .catch(() => {
                            Swal.fire(
                                this.trans('app.bonus.fail'),
                                this.trans('app.common.something_wrong') || 'Something went wrong',
                                'error'
                            );
                        })
                        .finally(() => {
                            this.isSubmitting = false;
                        });
                }
            }
        });
	
	</script>
@endpush

