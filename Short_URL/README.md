# Short URL

เว็บย่อลิงก์ด้วย React/Vite และ PHP: สร้างลิงก์สั้นพร้อม QR Code, เปิดลิงก์เพื่อไปยัง URL ต้นฉบับ และบันทึกยอดคลิกลงฐานข้อมูล หน้าเว็บดึงรายการลิงก์และสถิติใหม่ทุก 2 วินาที

> โปรเจกต์นี้ยังไม่มีระบบบัญชีผู้ใช้ รายการลิงก์และสถิติบนหน้าเว็บจึงไม่แยกตามผู้สร้าง ไม่ควรใช้กับลิงก์ที่ต้องเป็นความลับ

## สิ่งที่ต้องมี

- Node.js 20.19 ขึ้นไป หรือ 22.13 ขึ้นไป และ npm (ตรวจด้วย `node --version` และ `npm --version`)
- PHP 8.1 ขึ้นไป พร้อม `PDO` และ `pdo_sqlite` สำหรับการรันในเครื่อง หรือ `pdo_mysql` สำหรับ MySQL (ตรวจด้วย `php -v` และ `php -m`)
- สำหรับโฮสต์: เว็บเซิร์ฟเวอร์ที่รัน PHP ได้ และรองรับการ rewrite URL เช่น Apache ที่เปิด `mod_rewrite` และอนุญาต `.htaccess`

## โครงสร้างโปรเจกต์

```text
backend/             PHP API และการเชื่อมต่อฐานข้อมูล
  api/               สร้าง/อ่านลิงก์ และ redirect
  config/database.php
  data/              ไฟล์ SQLite ในเครื่อง (ไม่ส่งขึ้น Git)
  modals/            คลาสจัดการข้อมูล
Short_URL/            React/Vite frontend และ README นี้
```

คำสั่งด้านล่างเริ่มจากโฟลเดอร์หลักของ repository (โฟลเดอร์ที่มี `backend` และ `Short_URL`)

## ติดตั้งและรันในเครื่อง

เปิด terminal แรกเพื่อรัน PHP:

```powershell
php -S localhost:8000 -t backend
```

เปิด terminal ที่สองเพื่อรันหน้าเว็บ:

```powershell
cd Short_URL
npm ci
npm run dev
```

เปิด `http://localhost:5173` หน้าเว็บจะส่งคำขอ `/api` ไปยัง PHP ที่พอร์ต 8000 ผ่าน Vite proxy หากปิด terminal ใด terminal หนึ่ง การสร้างลิงก์จะใช้งานไม่ได้

เมื่อมีการเรียกใช้ฐานข้อมูลครั้งแรกในเครื่อง ระบบใช้ SQLite และสร้างไฟล์ `backend/data/short_url.sqlite` พร้อมตาราง `links_URL` และ `click_events` ให้อัตโนมัติ ไฟล์นี้ถูก Git ignore และไม่ได้เก็บข้อมูลของเว็บไซต์บนโฮสต์ หากไม่พบไฟล์ ให้ลองเปิดหน้าเว็บแล้วตรวจใหม่ และตรวจว่า PHP เปิด `pdo_sqlite` กับโฟลเดอร์ `backend/data` เขียนได้

**ข้อจำกัดในการรันด้วยคำสั่ง PHP ด้านบน:** PHP built-in server ไม่ได้ใช้ `.htaccess` จึงไม่รองรับการเปิด `/short-code` โดยตรงในเครื่อง ทดสอบการ redirect ผ่าน `http://localhost:8000/api/redirect.php?code=รหัสที่สร้าง` แทน ส่วนลิงก์สั้นแบบ `/short-code` ต้องตั้งค่า rewrite บนโฮสต์ตามหัวข้อด้านล่าง

## ทดสอบการทำงานในเครื่อง

1. กรอก URL ที่ขึ้นต้นด้วย `https://` หรือ `http://` แล้วกดสร้างลิงก์; นามแฝงและวันหมดอายุเป็นตัวเลือก
2. ตรวจว่าหน้าเว็บแสดงลิงก์สั้นและ QR Code (QR ชี้ไปยังลิงก์สั้นเดียวกัน)
3. สำหรับการรันในเครื่อง ให้นำ short code ไปเปิดที่ `http://localhost:8000/api/redirect.php?code=SHORT_CODE` แล้วตรวจว่าไปยัง URL ต้นฉบับ
4. กลับมาที่หน้าเว็บแล้วตรวจว่ายอดคลิกเพิ่มขึ้น; หรือเปิด `http://localhost:8000/api/links_URL/read.php` เพื่อตรวจ `clicks`
5. ตรวจข้อมูลโดยเปิดไฟล์ SQLite ด้วยโปรแกรมจัดการฐานข้อมูล: `links_URL` เก็บลิงก์ และ `click_events` เก็บหนึ่งแถวต่อการคลิก

## ใช้ MySQL บนเซิร์ฟเวอร์

สร้างฐานข้อมูล MySQL เปล่า แล้วรัน SQL นี้หนึ่งครั้ง (เช่นผ่าน phpMyAdmin) ก่อนเปิดใช้งานเว็บจริง MySQL **ไม่ได้สร้างตารางอัตโนมัติ** เหมือน SQLite ในเครื่อง:

```sql
CREATE TABLE links_URL (
  id INT AUTO_INCREMENT PRIMARY KEY,
  link_id VARCHAR(16) NOT NULL UNIQUE,
  short_code VARCHAR(32) NOT NULL UNIQUE,
  short_url TEXT NOT NULL,
  original_url TEXT NOT NULL,
  clicks INT NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  expires_at DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_clicked_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE click_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  click_id VARCHAR(20) NOT NULL UNIQUE,
  link_id VARCHAR(16) NOT NULL,
  clicked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_click_events_link_id (link_id),
  CONSTRAINT fk_click_events_link
    FOREIGN KEY (link_id) REFERENCES links_URL(link_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

กำหนด environment variables ของ PHP บนเซิร์ฟเวอร์:

```text
DB_DSN=mysql:host=localhost;dbname=ชื่อฐานข้อมูล;charset=utf8mb4
DB_USERNAME=ชื่อผู้ใช้ฐานข้อมูล
DB_PASSWORD=รหัสผ่านฐานข้อมูล
```

ให้ผู้ใช้ฐานข้อมูลมีสิทธิ์อ่านและเขียนทั้งสองตาราง รวมถึงสิทธิ์ปรับโครงสร้างตารางหากใช้ฐานข้อมูลรุ่นเก่าที่มีคอลัมน์ `expries_at` (โค้ดมีการย้ายข้อมูลแบบเก่าบางส่วน) อย่าใส่รหัสผ่านจริงใน README, source code หรือ GitHub หากโฮสต์ไม่มีช่องตั้ง environment variables ต้องจัดการไฟล์ตั้งค่าส่วนตัวที่ไม่เผยแพร่แยกต่างหากก่อนนำขึ้นใช้งาน

## Build และนำขึ้นโฮสต์

จากโฟลเดอร์ `Short_URL`:

```powershell
npm ci
npm run lint
npm run build
```

นำ **เนื้อหาภายใน** `Short_URL/dist/` (ไม่ใช่โฟลเดอร์ `dist` ทั้งก้อน) ไปไว้ที่ document root ของโดเมน แล้ววางโฟลเดอร์ `backend/api`, `backend/config`, `backend/modals` และ `backend/data` ที่ document root โดยตัดคำว่า `backend` ออก ผลลัพธ์ควรเป็นดังนี้:

```text
document-root/
  index.html
  assets/
  api/
  config/
  modals/
  data/
  .htaccess
```

ให้ `config` และ `data` ป้องกันการเข้าถึงจากเว็บ (ในโปรเจกต์มี `.htaccess` สำหรับโฟลเดอร์เหล่านี้) และให้ PHP เขียนไฟล์บันทึกข้อผิดพลาดใน `config` ได้ หากใช้ Apache ให้สร้าง `.htaccess` ที่ document root เพื่อส่ง short code ไปยัง PHP:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([A-Za-z0-9_-]{3,32})/?$ api/redirect.php?code=$1 [L,QSA]
```

ตัวอย่างนี้ใช้กับเว็บไซต์ที่อยู่ **ที่รากของโดเมน** เท่านั้น หากอยู่ใน subdirectory หรือใช้ Nginx ต้องปรับกฎ rewrite ตามเว็บเซิร์ฟเวอร์นั้น โปรเจกต์ไม่ได้ใส่ไฟล์ `.htaccess` ระดับ document root ไว้ใน repository เพราะตำแหน่งติดตั้งของแต่ละโฮสต์อาจต่างกัน

## ตรวจสอบหลังติดตั้ง

1. เปิดหน้าเว็บแล้วสร้างลิงก์ทดสอบ ตรวจว่ามีลิงก์สั้นและ QR Code
2. ใน phpMyAdmin ตรวจว่ามีแถวใหม่ใน `links_URL`
3. เปิด `https://โดเมน/SHORT_CODE` หรือสแกน QR แล้วตรวจว่าไปยัง URL ต้นฉบับ
4. ตรวจว่ามีแถวใหม่ใน `click_events` และค่า `clicks` ของลิงก์เพิ่มขึ้น หน้าเว็บจะดึงยอดใหม่ทุก 2 วินาทีเมื่อแท็บยังเปิดอยู่

หากหน้าเว็บว่างหรือสร้างลิงก์ไม่สำเร็จ ให้ตรวจว่าไฟล์ใน `dist` ถูกวางที่ document root และ `/api/links_URL/read.php` ตอบกลับได้ หาก API ตอบว่าฐานข้อมูลผิดพลาด ให้ตรวจ `DB_DSN`, ชื่อผู้ใช้/รหัสผ่าน, สิทธิ์ฐานข้อมูล, ตารางทั้งสอง และบันทึกข้อผิดพลาดของ PHP หากลิงก์สั้นเปิดไม่ได้แต่ `/api/redirect.php?code=...` เปิดได้ ให้ตรวจ rewrite rule
