---
name: commit
description: >-
  Automate git status checking, Zero-Touch validation, Docker compose pre-commit validation,
  continuous changelog tracking (CHANGELOG.md), and atomic conventional commits for php-workspace.
  Trigger ONLY when the user explicitly types `/commit` or asks to commit changes.
---

# Git Commit Automation Skill for PHP Workspace

Skill นี้ใช้สำหรับจัดการการตรวจสอบความปลอดภัย, Pre-commit Verification, การบันทึก Release Notes อัตโนมัติลงใน `CHANGELOG.md` และการ Commit โค้ด/คอนฟิกในโปรเจกต์ **`php-workspace` (Central Multi-PHP Development Hub)** ตามมาตรฐาน Conventional Commits อย่างเคร่งครัด

---

## 🛡️ กฎเหล็กประจำ Workspace (Safety & Zero-Touch Guard)

1. **Zero-Touch Project Code (MANDATORY & NON-NEGOTIABLE):**
   - โค้ดโปรเจกต์ของผู้ใช้ที่วางอยู่ใต้ `php/src/<project-name>/` มี Git Repository เป็นของตนเอง
   - **ห้าม Commit โค้ดใน `php/src/*` เข้า `php-workspace` โดยเด็ดขาด**
   - ก่อนรัน `git add` ต้องตรวจสอบเสมอว่า `.gitignore` ละเว้น `php/src/*` (ยกเว้น `!php/src/.gitkeep`)
   - หากตรวจพบว่ามีไฟล์โปรเจกต์ใน `php/src/` ติดสถานะ Staged ให้สั่ง `git reset HEAD php/src/<project>` ทันที

2. **Push Policy:**
   - การ Commit และ Tag ให้ทำบนเครื่อง Local เสมอ
   - **ห้ามรันคำสั่ง `git push` หรือ `git push --tags` โดยพลการ** เว้นแต่ผู้ใช้จะระบุอย่างชัดเจนว่าต้องการให้ Push

---

## 📋 Workflow เมื่อผู้ใช้สั่ง `/commit`

### 1. สแกนสถานะ Git และตรวจจับความปลอดภัย (Status & Zero-Touch Inspection)
- รัน `git status` ใน root ของ `php-workspace`
- ตรวจสอบรายการไฟล์ที่เปลี่ยนแปลง:
  * หากไม่มีการเปลี่ยนแปลงใดๆ ให้รายงานว่า `Working tree clean` และสิ้นสุดการทำงาน
  * ตรวจสอบว่าไม่มีไฟล์ภายใต้ `php/src/<project>/` หลุดเข้ามา

### 2. ตรวจสอบความถูกต้องของ Infrastructure ก่อน Commit (Pre-commit Verification)
ก่อนทำการ Commit ต้องทดสอบความถูกต้องของคอนฟิกเสมอ:
- **Docker Compose Validation:** รันตรวจสอบไวยากรณ์ `docker compose config -q`
  ```bash
  wsl bash -lc "cd '/mnt/e/PHP Project Management/php-workspace' && docker compose config -q"
  ```
  *(หากเกิด Error ห้าม Commit ให้แจ้งเตือนและแก้ไขคอนฟิกก่อน)*
- **PHP Syntax Check:** หากมีการแก้ไขไฟล์ PHP ส่วนกลาง (เช่น `php/shim.php`) ให้รันตรวจสอบไวยากรณ์:
  ```bash
  php -l php/shim.php
  ```
- **Apache Config Check:** หากมีการแก้ไข `php/apache-vhost.conf` ให้ตรวจความเรียบร้อยของคำสั่ง VirtualHost

---

### 3. บันทึก Changelog ต่อเนื่องอัตโนมัติ (Continuous Release Tracking)
🔴 **กฎเหล็ก CHANGELOG: "ทุกการเปลี่ยนแปลงฟังก์ชัน, คอนฟิก หรือความสามารถของ Workspace ต้องอัปเดต `CHANGELOG.md` เสมอ"**

1. **อ่านเวอร์ชันล่าสุด:** จากหัวข้อบนสุดของ `CHANGELOG.md` (เช่น `[1.0.0]`)
2. **คำนวณและปรับเปลี่ยนเวอร์ชันอัตโนมัติ (Semantic Versioning):**
   - **`feat` (เพิ่มเวอร์ชัน PHP ใหม่, เพิ่ม Extension, เพิ่ม Apache feature, หรือเพิ่ม Skill):**
     * ขยับตำแหน่ง Minor: `1.(x+1).0` (เช่น `1.0.0` $\rightarrow$ `1.1.0`)
   - **`fix` / `refactor` / `perf` (แก้บั๊กคอนฟิก, ปรับปรุง shim.php, แก้ไขพอร์ต):**
     * ขยับตำแหน่ง Patch: `1.x.(y+1)` (เช่น `1.0.0` $\rightarrow$ `1.0.1`)
   - **`docs` / `chore` (แก้ไข README, ปรับ `.gitignore`):**
     * ไม่ต้องขยับเวอร์ชัน ยกเว้นเป็นการอัปเดตเอกสารรอบใหญ่
3. **บันทึกลง `CHANGELOG.md`:**
   - เปิดบล็อกเวอร์ชันใหม่ที่คำนวณได้ (`[x.y.z] - YYYY-MM-DD`)
   - จัดหมวดหมู่รายการเปลี่ยนแปลงตามมาตรฐาน [Keep a Changelog](https://keepachangelog.com/):
     * `### Added` (สำหรับฟีเจอร์หรือบริการใหม่)
     * `### Changed` (สำหรับการปรับเปลี่ยนคอนฟิกหรือการทำงานเดิม)
     * `### Fixed` (สำหรับการแก้ไขปัญหาหรือบั๊ก)

---

### 4. สร้าง Conventional Commit Message (Strict Mandatory Scope)
บังคับใช้โครงสร้าง **`<type>(<scope>): <description>`** เสมอ (ห้ามละเว้น scope):

| Scope | บริบทการใช้งาน | ตัวอย่าง |
| :--- | :--- | :--- |
| **`docker`** | `docker-compose.yml`, network, ports, volumes, extra_hosts | `feat(docker): add php84 service on port 8427` |
| **`php`** | Dockerfile, Dockerfile-5.6, PHP extensions, INI settings | `fix(php): resolve debian bullseye archive eol` |
| **`apache`** | `apache-vhost.conf`, VirtualDocumentRoot, mod_rewrite | `feat(apache): support dynamic *.localhost routing` |
| **`shim`** | `php/shim.php`, auto_prepend_file, environment patches | `fix(shim): normalize HTTP_HOST port for dev environment` |
| **`agents`** | `.agents/AGENTS.md`, workspace rules, skills (`commit`) | `feat(agents): adapt commit automation skill` |
| **`docs`** | `README.md`, `CHANGELOG.md`, เอกสารคู่มือ | `docs(readme): add dual-routing guide and port table` |
| **`chore`** | `.gitignore`, ไฟล์ config ทั่วไป, จัดระเบียบโฟลเดอร์ | `chore(git): ignore all user project sources in php/src` |

---

### 5. ดำเนินการ Commit และติดแท็กเวอร์ชัน (Atomic Commit & Semantic Tagging)
1. **Stage ไฟล์ที่เกี่ยวข้อง:**
   ```bash
   git add docker-compose.yml php/ .agents/ README.md CHANGELOG.md .gitignore
   ```
   *(ตรวจสอบให้แน่ใจว่าไม่มี `php/src/*` ติดเข้ามา)*
2. **Commit:**
   ```bash
   git commit -m "<type>(<scope>): <description>"
   ```
3. **Semantic Tagging (เมื่อมีการขยับเวอร์ชัน):**
   - หากในรอบนี้มีการขยับเวอร์ชันใน `CHANGELOG.md` ให้สร้าง Annotated Tag ทันที:
     ```bash
     git tag -a vX.Y.Z -m "vX.Y.Z"
     ```

---

### 6. สรุปรายงานผล (Commit Report)
แสดงผลสรุปให้ผู้ใช้ทราบอย่างกระชับ:
- **Commit Hash & Message:** เช่น `a9d6f65 feat(agents): adapt commit automation skill`
- **Git Tag:** (เช่น `v1.0.1` ถ้ามี)
- **Changelog Updated:** สรุปรายการที่บันทึกลง `CHANGELOG.md`
- **Files Committed:** รายชื่อไฟล์ที่ถูกบันทึกใน Commit นี้
