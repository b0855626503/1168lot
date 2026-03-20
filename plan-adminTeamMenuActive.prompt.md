## แผน: แก้ Active เมนูทีมงาน

แผนงานนี้จะแยกจากงาน Lotto เดิม โดยโฟกัสเฉพาะพฤติกรรม sidebar ฝั่งทีมงาน (`admin::*`) ให้เมนูหลักและเมนูย่อยแสดงสถานะ `active`/`menu-open` ถูกต้องเมื่อเข้าหน้านั้นจริง ปัญหาหลักที่พบตอนนี้อยู่ที่ตรรกะใน `Gametech\Core\Tree` ซึ่งเทียบแบบ exact match ทำให้เมนูแม่ไม่ค้างเปิดเมื่ออยู่ในหน้าลูก และเสี่ยงเกิดอาการพับเมนูเอง

### Steps
1. ตรวจโครงสร้าง sidebar ฝั่งทีมงานจาก [packages/Gametech/Admin/src/Resources/views/layouts/menu.blade.php](/home/boat/Projects/1168lot/packages/Gametech/Admin/src/Resources/views/layouts/menu.blade.php) และ `Gametech\Core\Tree::getActive`, `Gametech\Core\Tree::getActives` ใน [packages/Gametech/Core/src/Tree.php](/home/boat/Projects/1168lot/packages/Gametech/Core/src/Tree.php)
2. ยืนยันแหล่งข้อมูลเมนูจาก `config('menu.admin')` ที่ถูก merge ผ่าน `Gametech\Admin\Providers\AdminServiceProvider` ใน [packages/Gametech/Admin/src/Providers/AdminServiceProvider.php](/home/boat/Projects/1168lot/packages/Gametech/Admin/src/Providers/AdminServiceProvider.php)
3. ปรับกติกา active ให้รองรับความสัมพันธ์ parent/child จาก key pattern เช่น `wallet` → `wallet.member`, `lotto` → `lotto.groups` โดยอิงโครงสร้างจริงใน [packages/Gametech/Lotto/src/Config/admin-menu.php](/home/boat/Projects/1168lot/packages/Gametech/Lotto/src/Config/admin-menu.php)
4. แยกเงื่อนไขให้เมนูหลักได้ `active` เมื่ออยู่หน้าแม่หรือหน้าลูกใต้แม่ และให้เมนูย่อยได้ `active` เฉพาะรายการที่ตรงกับหน้าปัจจุบัน โดยไม่พึ่งการคลิกเพื่อขยายเมนู
5. ทบทวนผลกระทบกับ view อื่นที่ใช้ `Tree` เดียวกัน โดยเฉพาะ sidebar ฝั่ง wallet ใน [packages/Gametech/Wallet/src/Resources/views/layouts/menu.blade.php](/home/boat/Projects/1168lot/packages/Gametech/Wallet/src/Resources/views/layouts/menu.blade.php) ว่าจะจำกัดขอบเขตเฉพาะ admin หรือยอมให้พฤติกรรมดีขึ้นทั้งสองฝั่ง

### Further Considerations
1. ต้องการให้เมนูหลัก “ติด active” ด้วยเมื่ออยู่ที่เมนูย่อยหรือไม่ — แนะนำให้ active ทั้งเมนูแม่และเมนูลูกเพื่อให้ผู้ใช้เห็นตำแหน่งปัจจุบันชัดเจน
2. งานนี้ควรจำกัดเฉพาะฝั่งทีมงานก่อน หรือรวม wallet ด้วย เพราะใช้ `Tree` ร่วมกันอยู่
3. หากมีเมนูที่ route ชี้ไปหน้าแรกของหมวดย่อยแทนหน้าแม่จริง ควรเก็บกติกานี้ไว้ตามเดิม ไม่เปลี่ยน `route` ใน [packages/Gametech/Admin/src/Providers/AdminServiceProvider.php](/home/boat/Projects/1168lot/packages/Gametech/Admin/src/Providers/AdminServiceProvider.php) โดยไม่จำเป็น
