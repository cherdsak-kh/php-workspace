---
name: check-php
description: >-
  Slash command (/check-php <project> [version|port]) to evaluate optimal PHP version,
  test route accessibility, HTTP status codes, and database connectivity, and diagnose
  configurations needed to make the project run locally.
---

# /check-php `<project>` `[version|port]`

Use this skill when the user runs `/check-php` or asks to test/verify/run a PHP project in `php-workspace-2`.

## Execution Workflow

1. **Verify Project & Mode of Operation:**
   - ตรวจสอบโฟลเดอร์ `php/src/<project>`
   - **กรณีที่ 1: ผู้ใช้ระบุเวอร์ชันมาด้วย** (เช่น `/check-php <project> 8.5`, `7.4` หรือแจ้งในข้อความ):
     * โฟกัสทดสอบบนพอร์ตของเวอร์ชันนั้นเป็นหลัก (เช่น 8.5 -> 8512, 7.4 -> 7433)
     * ประเมินความเข้ากันได้ของโค้ดกับเวอร์ชันที่ผู้ใช้ระบุ (เช่น deprecations, strict types, library compatibility)
   - **กรณีที่ 2: ผู้ใช้ไม่ได้ระบุเวอร์ชัน** (เช่น โคลนโปรเจกต์มาใหม่แล้วรัน `/check-php <project>`):
     * วิเคราะห์ Dependency, ไลบรารี และ syntax ของโปรเจกต์ (เช่น Smarty 2/3/4, ADODB, mysql_ vs mysqli, PHP syntax) เพื่อ **ประเมินและแนะนำเวอร์ชัน PHP ที่เหมาะสมที่สุด (Recommended Version)**
     * ทดสอบเปรียบเทียบข้ามเวอร์ชัน candidate (เช่น 5640, 7433, 8336, 8512)
2. **Test Both Access Modes via curl:**
   - **Subfolder Mode:** `http://localhost:<port>/<project>/` และ `http://localhost:<port>/<project>/weadmin/`
   - **Root Domain Mode:** `http://<project>.localhost:<port>/` และ `http://<project>.localhost:<port>/weadmin/`
3. **Diagnose & Trace Blocking Issues (Local Readiness):**
   - ตรวจสอบว่าโปรเจกรันได้จริงหรือไม่ (เช่น หน้าขาว 0 bytes, database connect failure, route/redirect 404)
   - ค้นหาสาเหตุที่แท้จริง (ดึง error logs หรือรันโหมด debug)
4. **Report & Provide Actionable Solutions in Thai:**
   - **สรุปเวอร์ชัน (Target / Recommended Version):** ระบุผลการประเมินเวอร์ชันพร้อมเหตุผล
   - **ตารางสรุปผลการทดสอบ (Status Matrix):** แสดง HTTP status, title, และการตอบสนอง
   - **แนวทางปรับแต่งให้รันใน Local ได้จริง (Actionable Fixes):**
     * ระบุไฟล์, บรรทัด, และค่าที่ต้องปรับ (เช่น DB Name, Host, $path_root, .htaccess)
     * แจ้งให้ผู้ใช้ทราบ และเสนอช่วยปรับแก้ไฟล์ config ให้พร้อมรันได้ทันที
