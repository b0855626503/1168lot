{{-- extend layout --}}
@extends('wallet::layouts.subpage')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        .homelogin {
            display: none !important;
        }
    </style>
@endpush

@section('content')

    <div class="container">
        <div class="logo">
            <img src="https://img5.pic.in.th/file/secure-sv1/PCewIWX8cB-2.png" alt="THEGRAND789">
        </div>

        <h3 id="title">เข้าสู่ระบบด้วยเบอร์โทรศัพท์</h3>
        <form method="POST" action="{{ route('customer.session.create') }}">
            @csrf
            <div class="input-group">
                <i>📱</i>
                <input id="phone" name="user_name" type="tel" placeholder="เบอร์โทรศัพท์" required>
            </div>

            <div class="input-group">
                <i>🔒</i>
                <input id="pass" name="password" type="password" placeholder="รหัสผ่าน" required>
                <span class="eye-icon">👁️</span>
            </div>

            <div id="forgot" class="forgot">
{{--                <a href="{{ $config->linelink }}" target="_blank">ติดต่อทีมงาน</a>--}}
            </div>

            <button id="loginBtn" type="submit" class="login-btn">เข้าสู่ระบบ</button>
        </form>
        <div id="langLabel" class="language-label">
            Language / ภาษา / ဘာသာစကား / ພາສາ / ភាសាខ្មែរ:
        </div>
        <div class="languages">
            <img class="flag" data-lang="th" src="https://flagcdn.com/w40/th.png" alt="TH" title="ไทย">
            <img class="flag" data-lang="en" src="https://flagcdn.com/w40/gb.png" alt="EN" title="English">
            <img class="flag" data-lang="mm" src="https://flagcdn.com/w40/mm.png" alt="MM" title="မြန်မာ">
            <img class="flag" data-lang="lo" src="https://flagcdn.com/w40/la.png" alt="LA" title="ລາວ">
            <img class="flag" data-lang="km" src="https://flagcdn.com/w40/kh.png" alt="KH" title="ភាសាខ្មែរ">
        </div>

        <div class="bottom-buttons">
            <a id="regBtn" href="{{ route('customer.session.store') }}" class="btn-register">📝 สมัครสมาชิก</a>
{{--            <a id="contactBtn" href="{{ $config->linelink }}" class="btn-contact">📞 ติดต่อ</a>--}}
        </div>
    </div>

    <script>
        const t = {
            th: {
                title: "เข้าสู่ระบบด้วยเบอร์โทรศัพท์", phone: "เบอร์โทรศัพท์", pass: "รหัสผ่าน",
                forgot: "ลืมรหัสผ่าน", login: "เข้าสู่ระบบ", reg: "สมัครสมาชิก", contact: "ติดต่อ",
                langLabel: "Language / ภาษา / ဘာသာစကား / ພາສາ / ភាសាខ្មែរ:"
            },
            en: {
                title: "Sign in with phone number", phone: "Phone number", pass: "Password",
                forgot: "Forgot password", login: "Sign in", reg: "Register", contact: "Contact",
                langLabel: "Language / ภาษา / ဘာသာစကား / ພາສາ / ភាសាខ្មែរ:"
            },
            mm: {
                title: "ဖုန်းနံပါတ်ဖြင့် ဝင်ရောက်ပါ", phone: "တယ်လီဖုန်းနံပါတ်", pass: "စကားဝှက်",
                forgot: "စကားဝှက်မေ့နေပါသလား", login: "လော့ဂ်အင်ဝင်ရန်", reg: "မှတ်ပုံတင်ရန်", contact: "ဆက်သွယ်ရန်",
                langLabel: "Language / ภาษา / ဘာသာစကား / ພາສາ / ភាសាខ្មែរ:"
            },
            lo: {
                title: "ເຂົ້າລະບົບດ້ວຍເບີໂທ", phone: "ເບີໂທ", pass: "ລະຫັດຜ່ານ",
                forgot: "ລືມລະຫັດຜ່ານ", login: "ເຂົ້າລະບົບ", reg: "ສະໝັກສະມາຊິກ", contact: "ຕິດຕໍ່",
                langLabel: "Language / ภาษา / ဘာသာစကား / ພາສາ / ភាសាខ្មែរ:"
            },
            km: {
                title: "ចូលប្រើដោយលេខទូរស័ព្ទ", phone: "លេខទូរស័ព្ទ", pass: "ពាក្យសម្ងាត់",
                forgot: "ភ្លេចពាក្យសម្ងាត់", login: "ចូលប្រើ", reg: "ចុះឈ្មោះ", contact: "ទំនាក់ទំនង",
                langLabel: "Language / ภาษา / ဘာသာစကား / ພາສາ / ភាសាខ្មែរ:"
            }
        };

        const els = {
            title: document.getElementById('title'),
            phone: document.getElementById('phone'),
            pass: document.getElementById('pass'),
            forgot: document.getElementById('forgot'),
            login: document.getElementById('loginBtn'),
            reg: document.getElementById('regBtn'),
            // contact: document.getElementById('contactBtn'),
            langLabel: document.getElementById('langLabel')
        };

        function setLang(code) {
            const L = t[code] || t.th;
            els.title.textContent = L.title;
            els.phone.placeholder = L.phone;
            els.pass.placeholder = L.pass;
            els.forgot.innerHTML = `<a href="https://line.me/R/ti/p/@grand789" target="_blank">${L.forgot}</a>`;
            els.login.textContent = L.login;
            els.reg.textContent = `📝 ${L.reg}`;
            // els.contact.textContent = `📞 ${L.contact}`;
            els.langLabel.textContent = L.langLabel;
            document.documentElement.lang = code;
            localStorage.setItem('lang', code);
        }

        document.querySelectorAll('.flag').forEach(f => {
            f.addEventListener('click', () => setLang(f.dataset.lang));
        });

        setLang(localStorage.getItem('lang') || 'th');
    </script>

@endsection
