# คู่มือติดตั้ง Laravel Echo + Pusher สำหรับ Next.js ฝั่งหน้าบ้าน

ฉบับละเอียดแบบทำตามทีละขั้นได้ทันที  
อ้างอิงและขยายความจากไฟล์ `API_FRONTEND_V1.md`

## เป้าหมาย
เมื่อสมาชิกล็อกอินหน้าเว็บ Next.js แล้ว ระบบต้องรับข้อมูลแบบ realtime ได้ เช่น:
- เติมเงินสำเร็จแล้วมียอดเด้ง
- ถอนเงินสำเร็จแล้วขึ้น toast
- หวยปิดรับหรือออกผลแล้วหน้าเว็บรู้ทันที

## คำศัพท์แบบง่าย
- **Next.js** = เว็บหน้าบ้าน
- **Laravel Echo** = ตัวช่วย listen event
- **pusher-js** = ตัวเชื่อม websocket protocol
- **Public channel** = ห้องกลาง ใครก็ฟังได้
- **Private channel** = ห้องส่วนตัวของสมาชิกแต่ละคน

## Endpoint สำคัญจากไฟล์ต้นทาง
| Endpoint | ต้องใช้ token | ใช้ทำอะไร |
|---|---:|---|
| `/realtime/config` | ไม่ต้อง | ดึง config สำหรับเปิด Echo |
| `/member/realtime-context` | ต้องใช้ | ดึง `member_code` และ `private_channel` |
| `/realtime/auth` | ต้องใช้ | auth private channel |
| `/member/balance` | ต้องใช้ | reconcile ยอดเงินจริง |
| `/member/heartbeat` | ต้องใช้ | อัปเดตสถานะออนไลน์ |

## ลำดับการทำงาน
1. user login สำเร็จ
2. ได้ `access_token`
3. เรียก `GET /realtime/config`
4. เรียก `GET /member/realtime-context`
5. สร้าง `Echo`
6. subscribe `public_channel`
7. subscribe `private_channel`
8. listen event
9. update UI
10. เรียก `/member/balance` เพื่อยืนยันยอดจริง

## ติดตั้ง package
```bash
npm install laravel-echo pusher-js
```

## ตั้งค่า env
```env
NEXT_PUBLIC_API_BASE_URL=https://api.example.com
NEXT_PUBLIC_APP_NAME=APP
```

## ตัวอย่าง response ของ `/realtime/config`
```json
{
  "success": true,
  "realtime": {
    "broadcaster": "pusher",
    "key": "app-key",
    "ws_host": "api.example.com",
    "ws_port": 6001,
    "ws_path": "",
    "ws_scheme": "http",
    "force_tls": false,
    "public_channel": "APP_events",
    "private_channel_member_template": "APP_members.{member_code}"
  }
}
```

## ตัวอย่าง response ของ `/member/realtime-context`
```json
{
  "success": true,
  "member_code": 10001,
  "private_channel": "APP_members.10001"
}
```

## ตัวอย่างการต่อ Echo
```ts
"use client";

import Echo from "laravel-echo";
import Pusher from "pusher-js";

export function createRealtimeConnection({
  token,
  privateChannel,
  realtime,
  onToast,
  onBalanceUpdate,
  onPublicEvent,
  onMemberEvent,
}: any) {
  (globalThis as any).Pusher = Pusher;

  const echo = new Echo({
    broadcaster: "pusher",
    key: realtime.key,
    wsHost: realtime.ws_host,
    wsPort: realtime.ws_port,
    wssPort: realtime.ws_port,
    wsPath: realtime.ws_path || "",
    forceTLS: realtime.force_tls,
    enabledTransports: ["ws", "wss"],
    authEndpoint: `${process.env.NEXT_PUBLIC_API_BASE_URL}/api/v1/realtime/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
    },
  });

  echo.channel(realtime.public_channel)
    .listen(".public.activity.updated", (event: any) => onPublicEvent(event))
    .listen(".lotto.draw_resulted", (event: any) => onPublicEvent(event));

  echo.private(privateChannel)
    .listen(".member.activity.updated", (event: any) => {
      onMemberEvent(event);

      if (event?.method === "deposit") {
        onToast(`เติมเงินสำเร็จ +${event?.data?.amount ?? 0} บาท`);
      }

      onBalanceUpdate(Number(event?.data?.balance ?? 0));
    })
    .listen(".member.balance.updated", (event: any) => {
      onMemberEvent(event);
      onBalanceUpdate(Number(event?.balance ?? 0));
    });

  return echo;
}
```

## จุดสำคัญ
- ใช้ `"use client"` เพราะ Echo ต้องทำงานใน browser
- `authEndpoint` ต้องชี้ไป `/realtime/auth`
- private channel ต้องใช้ค่าจริงจาก API
- อย่าสร้าง Echo ซ้ำหลายรอบ
- เมื่อมี event เรื่องยอดเงิน ให้ reconcile ด้วย `/member/balance`

## ปัญหาที่เจอบ่อย
| อาการ | สาเหตุ |
|---|---|
| public ได้ แต่ private ไม่ได้ | ไม่ส่ง Bearer token |
| event ไม่มา | ชื่อ event ไม่ตรง หรือ backend ไม่ broadcast |
| event ซ้ำ | สร้าง Echo ซ้ำหลาย instance |
| ยอดไม่ตรง | ไม่มี reconcile ด้วย `/member/balance` |

## สรุปสั้น
- ติดตั้ง `laravel-echo` และ `pusher-js`
- login ให้ได้ token
- เรียก `/realtime/config` และ `/member/realtime-context`
- สร้าง Echo พร้อม `authEndpoint`
- listen public + private channel
- เมื่อมี event ให้ toast + update state + reconcile balance