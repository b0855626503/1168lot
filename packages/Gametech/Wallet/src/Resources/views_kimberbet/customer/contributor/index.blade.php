@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('content')

    <contributor
            :faststart="{{$faststart}}"
            :profile='@json($profile)'
            contributor-url="{{ route('customer.contributor.register', $profile->code) }}"
    ></contributor>

@endsection

{{--@push('components')--}}
{{--    <script>--}}
{{--        // Vue hook เบา ๆ สำหรับเพจนี้--}}
{{--        new Vue({--}}
{{--            el: '#i18n-page',--}}
{{--            methods: {--}}
{{--                // trans(key, replace = {}) {--}}
{{--                //     let t = key.split('.').reduce((o,k) => (o && o[k]) ? o[k] : null, window.i18n);--}}
{{--                //     if (typeof t !== 'string') t = key; // fallback เป็น key ถ้าไม่เจอ--}}
{{--                //     for (const p in replace) t = t.replace(`:${p}`, replace[p]);--}}
{{--                //     return t;--}}
{{--                // }--}}
{{--            },--}}
{{--            mounted(){--}}
{{--                // เมื่อมีการสลับภาษา (จาก AppLocale.switchTo) ให้ re-render ทันที--}}
{{--                this._onLocaleChange = () => { this.$forceUpdate(); };--}}
{{--                window.addEventListener('app:locale:changed', this._onLocaleChange);--}}
{{--            },--}}
{{--            beforeDestroy(){--}}
{{--                window.removeEventListener('app:locale:changed', this._onLocaleChange);--}}
{{--            }--}}
{{--        });--}}
{{--    </script>--}}
{{--@endpush--}}
