# คู่มือการใช้งาน One-Click Launcher (`start.bat`)
**PHP Multi-Version Hub — Windows Automation & Control Center**

---

## 📌 บทนำและภาพรวม

`start.bat` เป็นสคริปต์อัตโนมัติ (Interactive Batch Launcher) สำหรับสภาพแวดล้อม Windows ออกแบบมาเพื่ออำนวยความสะดวกในการควบคุม เริ่มต้น (Start), หยุด (Stop), และเปิดเว็บเบราว์เซอร์สำหรับคอนเทนเนอร์ Docker ทั้งหมดในโปรเจกต์ โดยไม่ต้องพิมพ์คำสั่ง Docker Compose ยาวๆ ด้วยตนเอง

### คุณสมบัติเด่น (Key Features):
- **ตรวจจับสภาพแวดล้อมอัตโนมัติ:** รองรับทั้งการรัน Docker แบบ Native บน Windows และการเชื่อมต่อผ่าน **WSL2** (`wsl docker`) อย่างราบรื่น
- **ระบบความปลอดภัย:** ตรวจสอบความพร้อมของคอนเทนเนอร์ก่อนเปิดเบราว์เซอร์ เพื่อป้องกันข้อผิดพลาด *Connection Refused*
- **จัดการ Portainer อัตโนมัติ:** ติดตั้งและดึง API Key/Setup Token จาก Portainer ลงไฟล์ `portainer-token.json` โดยอัตโนมัติ
- **เมนูควบคุมครบวงจร:** รวม 15 เมนูหลัก ครอบคลุม PHP Runtime, Portainer, phpMyAdmin, และระบบวินิจฉัย (Diagnostics)

---

## 🚀 วิธีการเปิดใช้งาน

1. **ผ่าน File Explorer (ง่ายที่สุด):**
   - ไปที่โฟลเดอร์โปรเจกต์ `php-workspace\`
   - ดับเบิลคลิกที่ไฟล์ **`start.bat`**
2. **ผ่าน Terminal (PowerShell / Command Prompt):**
   ```cmd
   cd "e:\PHP Project Management\php-workspace"
   .\start.bat
   ```

---

## 📋 รายละเอียดเมนูการใช้งาน (Menu Cheatsheet)

```text
==============================================================
             PHP Multi-Version Hub Launcher
==============================================================

 [ PHP RUNTIME SERVICES ]
  1) Start Recommended PHP (PHP 7.4 + PHP 8.3)
  2) Start Specific PHP Version (Select from 5.6 to 8.5)
  3) Start All PHP Containers (PHP 5.6 to 8.5)
  4) Stop PHP Containers

 [ CONTAINER MANAGER (PORTAINER) ]
  5) Start Portainer Web App (Port 9000)
  6) Stop Portainer Web App
  7) Setup / Refresh Portainer Token (Saved to File)

 [ DATABASE TOOLS (PHPMYADMIN) ]
  8) Start phpMyAdmin (Port 8081 - Connects to mysql-sv)
  9) Stop phpMyAdmin

 [ BROWSER SHORTCUTS ]
 10) Open Hub Dashboard (Auto-detect active PHP container)
 11) Open Portainer UI  (http://localhost:9000)
 12) Open phpMyAdmin    (http://localhost:8081)

 [ SYSTEM AND DIAGNOSTICS ]
 13) View Container Status (docker compose ps)
 14) Detect Host and Docker Environment
 15) Stop ALL Running Containers (Full Shutdown)
  0) Exit
==============================================================
```

---

### หมวดที่ 1: PHP Runtime Services (เมนู 1 - 4)

| ตัวเลือก | คำสั่ง | รายละเอียด |
| :--- | :--- | :--- |
| **`1`** | **Start Recommended PHP** | สั่งรันคอนเทนเนอร์คู่มาตรฐาน **PHP 7.4** (`:7433`) และ **PHP 8.3** (`:8336`) เหมาะสำหรับการพัฒนาทั่วไปส่วนใหญ่ |
| **`2`** | **Start Specific PHP Version** | เปิดเมนูย่อยเพื่อเลือกเปิดเฉพาะ PHP เวอร์ชันที่ต้องการ:<br>• `[1]` PHP 5.6 (Port 5640) - Legacy (mysql ext, Smarty 2)<br>• `[2]` PHP 7.4 (Port 7433) - Transitional (Smarty 3/4)<br>• `[3]` PHP 8.0 (Port 8030) - PHP 8.0 transition<br>• `[4]` PHP 8.1 (Port 8131) - Early strict mode<br>• `[5]` PHP 8.2 (Port 8235) - Modern active<br>• `[6]` PHP 8.3 (Port 8336) - Hub default standard<br>• `[7]` PHP 8.4 (Port 8427) - Latest stable<br>• `[8]` PHP 8.5 (Port 8512) - Experimental / next-gen |
| **`3`** | **Start All PHP Containers** | สั่งรันคอนเทนเนอร์ PHP ทั้ง 8 ตัวพร้อมกัน (PHP 5.6 ถึง 8.5) |
| **`4`** | **Stop PHP Containers** | เปิดเมนูย่อยเพื่อเลือกหยุด (Stop) คอนเทนเนอร์ PHP ทั้งหมด หรือเลือกหยุดเฉพาะเวอร์ชัน เพื่อประหยัด RAM/CPU ของเครื่อง |

---

### หมวดที่ 2: Container Manager — Portainer (เมนู 5 - 7)

| ตัวเลือก | คำสั่ง | รายละเอียด |
| :--- | :--- | :--- |
| **`5`** | **Start Portainer Web App** | สั่งรันคอนเทนเนอร์ `portainer-hub` บนพอร์ต `9000` พร้อมดึงรหัสผ่านและ Setup Token อัตโนมัติ |
| **`6`** | **Stop Portainer Web App** | สั่งหยุดคอนเทนเนอร์ Portainer |
| **`7`** | **Setup / Refresh Portainer Token** | รันสคริปต์ `setup-portainer.ps1` หรือ `setup-portainer.py` เพื่อดึง Token และ API Key มาบันทึกใน `portainer-token.json` สำหรับนำไปแสดงบนแดชบอร์ด |

---

### หมวดที่ 3: Database Tools — phpMyAdmin (เมนู 8 - 9)

| ตัวเลือก | คำสั่ง | รายละเอียด |
| :--- | :--- | :--- |
| **`8`** | **Start phpMyAdmin** | สั่งรันคอนเทนเนอร์ `pma-hub` บนพอร์ต `8081` โดยเชื่อมต่อไปยัง MySQL Gateway (`mysql-sv:3306`) บนเครื่อง Host อัตโนมัติ |
| **`9`** | **Stop phpMyAdmin** | สั่งหยุดคอนเทนเนอร์ phpMyAdmin |

---

### หมวดที่ 4: Browser Shortcuts (เมนู 10 - 12)

| ตัวเลือก | คำสั่ง | รายละเอียด |
| :--- | :--- | :--- |
| **`10`** | **Open Hub Dashboard** | ตรวจสอบว่าคอนเทนเนอร์ PHP ใดเปิดอยู่ แล้วเปิดเบราว์เซอร์ไปยังพอร์ตนั้น (หากยังไม่มีตัวใดเปิด จะมี prompt ถามว่าต้องการเริ่ม PHP 8.3 เลยหรือไม่) |
| **`11`** | **Open Portainer UI** | ตรวจสอบว่า Portainer เปิดอยู่หรือไม่ หากเปิดจะนำไปยัง `http://localhost:9000/` หากยังไม่เปิดจะมี prompt ถามเพื่อเริ่มทำงานทันที |
| **`12`** | **Open phpMyAdmin** | ตรวจสอบว่า phpMyAdmin เปิดอยู่หรือไม่ หากเปิดจะนำไปยัง `http://localhost:8081/` หากยังไม่เปิดจะมี prompt ถามเพื่อเริ่มทำงานทันที |

---

### หมวดที่ 5: System & Diagnostics (เมนู 13 - 15, 0)

| ตัวเลือก | คำสั่ง | รายละเอียด |
| :--- | :--- | :--- |
| **`13`** | **View Container Status** | แสดงตารางสถานะของคอนเทนเนอร์ทั้งหมด (`docker compose ps`) |
| **`14`** | **Detect Host and Docker Environment** | รันสคริปต์วิเคราะห์สภาพแวดล้อม Host, Docker Engine, Gateway IP, และพอร์ตต่างๆ |
| **`15`** | **Stop ALL Running Containers** | สั่งหยุดและลบคอนเทนเนอร์ทั้งหมดใน Workspace อย่างปลอดภัย (`docker compose down`) |
| **`0`** | **Exit** | ออกจากโปรแกรม |

---

## 💡 รูปแบบการใช้งานจริงที่แนะนำ (Common Workflows)

### 1. เริ่มต้นพัฒนาประจำวันทั่วไป
1. เปิด `start.bat` กด `1` (Start Recommended PHP: 7.4 + 8.3)
2. กด `10` (Open Hub Dashboard) เบราว์เซอร์จะเปิดหน้าเว็บ `http://localhost:8336/` ทันที
3. เลือกโปรเจกต์ที่ต้องการทดสอบในหน้าแดชบอร์ด

### 2. ต้องการทดสอบโค้ดบน PHP รุ่นเก่า (PHP 5.6)
1. เปิด `start.bat` กด `2` แล้วเลือก `1` (PHP 5.6)
2. บนหน้าเว็บแดชบอร์ด ไฟสถานะของ `PHP 5.6 (:5640)` จะเปลี่ยนเป็นสีเขียว และปุ่มจะสามารถคลิกใช้งานได้ทันที

### 3. ต้องการจัดการฐานข้อมูลผ่าน phpMyAdmin
1. เปิด `start.bat` กด `8` (Start phpMyAdmin)
2. กด `12` (Open phpMyAdmin) หรือคลิกลิงก์บนหน้า Hub Dashboard ที่พอร์ต `:8081`

### 4. ปิดเครื่อง / เลิกงานประจำวัน
1. เปิด `start.bat` กด `15` (Stop ALL Running Containers) คอนเทนเนอร์ทั้งหมดจะหยุดทำงานเพื่อคืน RAM ให้กับระบบ
2. กด `0` เพื่อปิด Launcher

---

## 🔧 การแก้ไขปัญหาเบื้องต้น (Troubleshooting)

| ปัญหาที่พบ | สาเหตุ | วิธีแก้ไข |
| :--- | :--- | :--- |
| `[ERROR] Neither Docker nor WSL was found` | ยังไม่ได้เปิด Docker Desktop หรือยังไม่ได้ติดตั้ง WSL2 | ให้เปิด Docker Desktop หรือติดตั้ง WSL2 ก่อนรันสคริปต์ |
| แดชบอร์ดแสดง `[ปิดอยู่]` และคลิกไม่ได้ | คอนเทนเนอร์ของพอร์ตหรือเครื่องมือนั้นยังไม่ได้ถูก Start | เปิด `start.bat` แล้วเลือกกดเมนูเพื่อ Start คอนเทนเนอร์ตัวที่ต้องการ |
| พอร์ต 3306 ใช้งานไม่ได้ | Service `mysql-sv` บนเครื่อง Host ยังไม่เปิด | ตรวจสอบคอนเทนเนอร์ MySQL บนเครื่อง Host หรือให้ `start.bat` สั่งเริ่ม MySQL 8.0 ในโหมดเสริม |
