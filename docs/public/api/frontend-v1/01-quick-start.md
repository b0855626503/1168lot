# Frontend API V1 - Quick Start

อัปเดตล่าสุด: 2026-04-30

## Quick Example (Login -> Balance)

### 1) Login

`POST /api/v1/auth/login`

```bash
curl -X POST "/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -H "X-Language: th" \
  -d '{
    "user_name": "0900000014",
    "password": "pass1234"
  }'
```

### 2) Call Balance with Bearer token

`GET /api/v1/member/balance`

```bash
curl "/api/v1/member/balance" \
  -H "Authorization: Bearer eyJ..." \
  -H "X-Language: th"
```

### Axios

```js
const login = await axios.post('/api/v1/auth/login', {
  user_name: '0900000014',
  password: 'pass1234',
}, { headers: { 'X-Language': 'th' } });

const token = login.data?.data?.access_token;

const balance = await axios.get('/api/v1/member/balance', {
  headers: {
    Authorization: `Bearer ${token}`,
    'X-Language': 'th',
  },
});
```
