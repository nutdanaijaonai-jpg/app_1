FROM php:8.2-apache

# Install PDO MySQL and required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite headers

# Configure Apache to allow .htaccess overrides
RUN echo '<Directory /var/www/html>' >> /etc/apache2/apache2.conf \
    && echo '    Options Indexes FollowSymLinks' >> /etc/apache2/apache2.conf \
    && echo '    AllowOverride All' >> /etc/apache2/apache2.conf \
    && echo '    Require all granted' >> /etc/apache2/apache2.conf \
    && echo '</Directory>' >> /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html/backend-php

# Copy project files into container
COPY . /var/www/html/backend-php

RUN printf '<?php header("Location: /backend-php/admin/"); exit;' > /var/www/html/index.php

# Expose HTTP port
EXPOSE 80
