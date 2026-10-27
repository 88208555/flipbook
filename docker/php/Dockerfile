FROM php:8.2-fpm-bookworm

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# 安装 PHP 扩展
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo_mysql \
        curl \
        mbstring \
        xml \
        zip \
        intl \
        opcache

# Redis 扩展（Discuz X5 RESTful 与 JSON 编辑区块的运行前置）
# pecl.php.net 在当前网络下不可达，改为使用构建上下文中的 phpredis 源码包编译
COPY phpredis.tar.gz /tmp/phpredis.tar.gz
RUN mkdir -p /tmp/phpredis \
    && tar -xzf /tmp/phpredis.tar.gz -C /tmp/phpredis --strip-components=1 \
    && (cd /tmp/phpredis && phpize && ./configure --with-php-config=/usr/local/bin/php-config && make -j$(nproc) && make install) \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/phpredis /tmp/phpredis.tar.gz

# PHP 配置
RUN { \
        echo 'upload_max_filesize = 50M'; \
        echo 'post_max_size = 50M'; \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 300'; \
        echo 'date.timezone = Asia/Shanghai'; \
    } > /usr/local/etc/php/conf.d/discuz.ini

WORKDIR /var/www/html
