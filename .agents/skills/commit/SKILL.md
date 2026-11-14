---
name: commit
description: >-
  Automate git status checking, continuous release tracking, pre-commit validation, and atomic commits across the monorepo repositories (easybook-app, easybook-service, easybook-project-manager).
  Trigger ONLY when the user explicitly types `/commit` or asks to commit changes.
---

# Git Commit Automation Skill for EasyBook

Skill นี้ใช้สำหรับจัดการการ Commit โค้ดและเอกสารในโปรเจกต์ EasyBook App ตามมาตรฐาน Conventional Commits, การสะสมประวัติการเปลี่ยนแปลงลงไฟล์ Release อัตโนมัติ (Continuous Changelog Buffering) และกฎความปลอดภัยของโปรเจกต์

## Workflow เมื่อผู้ใช้สั่ง `/commit`

1. **สแกนสถานะ Git ทั่วทั้งระบบ (Status Inspection):**
   - ตรวจสอบ `git status` ใน 3 Repositories:
     1. `easybook-app/` (Frontend)
     2. `easybook-service/` (Backend)
     3. `easybook-project-manager/` (Root Workspace & Planning)

2. **บันทึก Release Notes ต่อเนื่องอัตโนมัติ (Continuous Release Tracking) — บังคับ 100%:**
   - 🔴 **กฎเหล็ก CHANGELOG (MANDATORY & NON-NEGOTIABLE): "อะไรที่เป็น changelog ต้องอัปเดต release เสมอ"**
   - **เกณฑ์การตัดสินใจ (Changelog Qualification):** หากการเปลี่ยนแปลงมีผลต่อผู้ใช้หรือผู้ดูแลระบบ (UI, UX, Responsive, หน้าจอใหม่, ปรับแต่งสไตล์, เพิ่มปุ่ม, แก้ไขการแสดงผล, แก้บั๊ก, หรือฟังก์ชันการทำงานหลัก):
     * **ถือเป็น Changelog ทั้งสิ้น** และ **ต้องอัปเดต `releases.ts` เสมอ ห้ามละเว้นเด็ดขาด**
     * *(ห้ามอ้าง Do-NOT-Touch list ของไฟล์แผนงานอื่นมาเป็นเหตุผลในการข้ามไฟล์ releases.ts)*
   - **ลำดับขั้นตอนปฏิบัติ:**
     1. **ตรวจสอบเวอร์ชันปัจจุบัน:** อ่าน `version` จาก `easybook-app/package.json` (ระบบอยู่ในสายการผลิต `1.x.y`)
     2. **คำนวณและปรับเปลี่ยนเวอร์ชันอัตโนมัติ (Production Auto-Bumping & Precedence Rules):**
        - วิเคราะห์รายการเปลี่ยนแปลงที่จะบันทึกลงใน Release Notes:
          * **กรณีมีรายการกลุ่ม `'ใหม่'` (New Feature / `feat`) $\ge 1$ รายการ (Highest Precedence):**
            - ปรับขยับตำแหน่ง **`x`** (Minor bump: `1.(x+1).0`) และรีเซ็ต `y = 0` (เช่น `1.0.0` $\rightarrow$ `1.1.0`)
          * **กรณีมีเฉพาะรายการกลุ่ม `'ปรับปรุง'` หรือ `'แก้ไข'` (Improvement / Fix / `refactor` / `perf` / UI Tweak):**
            - ปรับขยับตำแหน่ง **`y`** (Patch bump: `1.x.(y+1)`) (เช่น `1.0.0` $\rightarrow$ `1.0.1`)
          * **กรณีงานบำรุงรักษาภายในที่ไม่กระทบผู้ใช้ (Non-Functional Changes):**
            - หาก Commit มีเฉพาะ `chore(deps)`, `chore(ci)`, หรือ `docs(planning)` ที่ไม่มีผลกระทบต่อ UI/UX หรือฟังก์ชันผู้ใช้ **ไม่ต้องขยับเวอร์ชัน** และไม่ต้องลง `releases.ts`
        - **ซิงก์เวอร์ชัน Monorepo เสมอ:** ปรับค่า `version` ให้ตรงกันทั้งใน `easybook-app/package.json` และ `easybook-service/package.json`
     3. **อัปเดตไฟล์ Release ของแต่ละ Portal ทันที (ขออนุมัติเขียนไฟล์ก่อนรัน Commit):**
        - **Admin Portal:** `easybook-app/src/admin-portal/lib/releases.ts` (ใช้ภาษาทางการ/งานบริหารจัดการ)
        - **Client Portal:** `easybook-app/src/client-portal/lib/releases.ts` (ใช้ภาษาเข้าใจง่ายสำหรับผู้ใช้ LINE)
     4. **รูปแบบและโครงสร้างข้อมูล:**
        - จัดหมวดหมู่ของสิ่งที่ทำลงในกลุ่มที่ถูกต้อง: `'ใหม่'`, `'ปรับปรุง'`, หรือ `'แก้ไข'`
        - เปิดบล็อกเวอร์ชันใหม่ที่คำนวณได้ (`1.x.y`) ไว้ที่หัว Array โดยระบุ `date` เป็นวันที่ปัจจุบันในรูปแบบ พ.ศ. (เช่น `'3 ต.ค. 2569'`)
     5. **มัดรวมเข้า Commit เดียวกัน (Strict Atomic Commit):**
        - ไฟล์ `package.json` ทั้งสองฝั่ง และไฟล์ `releases.ts` จะต้องถูกบันทึกและรัน `npm run build && npm run lint` ตรวจสอบร่วมกัน และถูก commit อยู่ใน Commit เดียวกับโค้ดจริงเสมอ (ห้ามปล่อยให้โค้ด commit ขึ้นไปก่อนแล้วมาตามแก้ changelog ทีหลัง)

3. **ตรวจสอบและอัปเดตแผนผังสิทธิ์และความสามารถของผู้ใช้งาน (Actor Use Case & Capabilities Matrix):**
   - วิเคราะห์การเปลี่ยนแปลงในรอบ Commit นั้นในมุมมองของ **Use Case ("ใครทำอะไรได้บ้างในระบบ")**:
     * **มีความสามารถใหม่ (New Capability / Action):** ที่ผู้ใช้กลุ่มใดกลุ่มหนึ่ง (Super Admin, หัวหน้าฝ่ายฯ, เจ้าหน้าที่, หรือ ผู้ใช้ LINE) ทำได้เพิ่มขึ้นหรือไม่
     * **มีสิทธิ์ที่ถูกเพิกถอนหรือปิดกั้น (Revoked / Restricted Action):** จากผู้ใช้กลุ่มใดกลุ่มหนึ่งหรือไม่
     * **มีการสลับหรือเปลี่ยนแปลงระดับสิทธิ์:** ระหว่าง 4 บทบาทหลักในระบบหรือไม่
   - หากตรวจพบว่ามีผลกระทบต่อ Use Case หรือสิทธิ์ของผู้ใช้งานจริง:
     * **ต้องเปิดไฟล์ `project-documents/markdown/ตารางวิเคราะห์สิทธิ์และความสามารถของผู้ใช้งาน.md`** เพื่อตรวจสอบและอัปเดตรายการความสามารถ, สิทธิ์ (✅/❌), จุดพิสูจน์บน UI (ปุ่ม/หน้าจอ), และ Endpoint ให้ตรงตาม As-Built จริง
     * **กฎการสร้างหมวดหมู่ใหม่:** หากมีความสามารถใหม่ที่พัฒนาขึ้น แต่ไม่เข้ากับ 7 หมวดหมู่เดิมที่มีอยู่ **อนุญาตให้สร้างหมวดหมู่ใหม่ (New Module)** ต่อท้ายในตารางได้ โดยตั้งชื่อหมวดหมู่อย่างเป็นทางการและกระชับ
     * ขออนุมัติแก้ไขไฟล์ตามระเบียบ Write Approval Workflow และบันทึกรวมใน Commit เดียวกัน
   - *หากเป็นการ Refactor ภายใน, การแก้บั๊กแสดงผล/CSS, หรือ Background Worker ที่ไม่เปลี่ยนความสามารถของผู้ใช้ ให้ข้ามขั้นตอนนี้ได้*

4. **ตรวจสอบการทดสอบและ Type Safety ก่อน Commit (Pre-commit Verification):**
   - **ฝั่ง Frontend (`easybook-app`):** หากมีไฟล์เปลี่ยนแปลง ให้รัน `npm run build && npm run lint` เพื่อการันตี Type Safety และ Linter
   - **ฝั่ง Backend (`easybook-service`):** หากมีไฟล์เปลี่ยนแปลง ให้รัน `npm run build && npm run lint && npm test`

5. **สร้าง Conventional Commit Message (Strict Mandatory Scope):**
   - วิเคราะห์เนื้อหาการเปลี่ยนแปลง (Diff / Status)
   - **บังคับใช้รูปแบบ `<type>(<scope>): <description>` เสมอ (ห้ามละเว้น scope):**
     - `feat(client-portal): ...`
     - `fix(auth): ...`
     - `chore(cleanup): ...`
     - `chore(assets): ...`
     - `docs(planning): ...`

6. **ดำเนินการ Commit และติดแท็กเวอร์ชัน (Atomic Git Commit & Semantic Tagging):**
   - รัน `git add -A` และ `git commit -m "<conventional message>"` ในแต่ละ Repository ที่มีไฟล์เปลี่ยนแปลง
   - **การติดแท็ก Git (Git Tagging):**
     * เมื่อมีการขยับเวอร์ชัน (`1.x.y`) จากการปล่อยฟีเจอร์ใหม่ ('ใหม่') หรือปรับปรุง/แก้ไข ('ปรับปรุง'/'แก้ไข') ให้สร้าง Annotated Tag รูปแบบ `v1.x.y` ทันทีหลังการ Commit สำเร็จ:
       ```bash
       git tag -a v1.x.y -m "v1.x.y"
       ```
     * ดำเนินการติดแท็กในทุก Repository ที่มีการปรับเวอร์ชัน (`easybook-app/` และ `easybook-service/`) ให้ตรงกัน
     * หากรอบ Commit นั้นไม่มีการขยับเวอร์ชัน (Non-functional / chore) **ไม่ต้องติดแท็ก**
     * *ข้อควรระวัง:* แท็กจะถูกสร้างบนเครื่อง Local เท่านั้น **ห้ามรันคำสั่ง `git push` หรือ `git push --tags` โดยเด็ดขาด** (การ Push เป็นสิทธิ์ขาดของผู้พัฒนา)
   - หากไม่มีการเปลี่ยนแปลงใน Repository ใด ให้รายงานว่า Working tree clean

7. **สรุปรายงานผล (Commit Report):**
   - แสดงรายการ Commit Hash, Commit Message, Git Tag (ถ้ามี) และสรุปไฟล์ที่ถูกบันทึกในแต่ละ Repository อย่างชัดเจน
