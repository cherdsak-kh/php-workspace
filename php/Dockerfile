ARG PHP_VERSION=8.3.36-apache
FROM php:${PHP_VERSION}

# กำหนด ServerName เพื่อไม่ให้ขึ้น warning ตอน start apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# ติดตั้ง docker-php-extension-installer เพื่อจัดการ extensions และ dependencies ข้าม PHP version/Debian EOL อัตโนมัติ
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# ติดตั้ง extensions พื้นฐาน (mysqli, pdo, pdo_mysql, gd, zip, mbstring)
RUN install-php-extensions mysqli pdo_mysql gd zip mbstring

# เปิดใช้งาน mod_rewrite และ mod_vhost_alias สำหรับ Apache
RUN a2enmod rewrite vhost_alias headers

# คอนฟิก Apache VirtualHost (รองรับทั้ง localhost:<port>/<project>/ และ <project>.localhost:<port>/)
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# ใช้ php.ini-development เพื่อให้แสดง error เวลาเขียนโค้ด
RUN if [ -f "$PHP_INI_DIR/php.ini-development" ]; then cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"; fi

# คอนฟิก custom.ini และ auto_prepend_file
COPY shim.php /etc/php/shim.php
RUN { \
    echo "short_open_tag = On"; \
    echo "memory_limit = 256M"; \
    echo "upload_max_filesize = 64M"; \
    echo "post_max_size = 64M"; \
    echo "date.timezone = Asia/Bangkok"; \
    echo "auto_prepend_file = /etc/php/shim.php"; \
} >> "$PHP_INI_DIR/conf.d/custom.ini"
