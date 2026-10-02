# LinkMint URL Shortener

เว็บย่อลิงก์ React + PHP ที่สร้าง Short URL, QR Code, redirect ไป URL ต้นฉบับ และบันทึกจำนวนคลิกลงฐานข้อมูล

## เริ่มใช้งานในเครื่อง

เปิด terminal แรกที่โฟลเดอร์หลักของโปรเจกต์:

```powershell
php -S localhost:8000 -t backend
```

เปิด terminal ที่สอง:

```powershell
cd Short_URL
npm install
npm run dev
```

จากนั้นเปิด `http://localhost:5173`

ระบบใช้ SQLite ที่ `backend/data/short_url.sqlite` และสร้างตารางให้อัตโนมัติ

## ใช้ MySQL บนเซิร์ฟเวอร์

กำหนด `DB_DSN`, `DB_USERNAME` และ `DB_PASSWORD` ใน environment ของเซิร์ฟเวอร์ ตัวอย่าง DSN:

```text
mysql:host=localhost;dbname=short_url;charset=utf8mb4
```

อย่าเก็บรหัสผ่านฐานข้อมูลไว้ใน source code และตั้ง document root ให้ชี้ไปยังโฟลเดอร์ `backend`

## ตรวจสอบก่อน deploy

```powershell
cd Short_URL
npm run lint
npm run build
```
