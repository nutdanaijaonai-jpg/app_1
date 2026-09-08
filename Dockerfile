FROM php:8.2-apache

# Install PDO MySQL, MySQLi and required extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite headers

# Configure Apache directory permissions and allow .htaccess
RUN echo '<Directory /var/www/html>' >> /etc/apache2/apache2.conf \
    && echo '    Options -Indexes +FollowSymLinks' >> /etc/apache2/apache2.conf \
    && echo '    AllowOverride All' >> /etc/apache2/apache2.conf \
    && echo '    Require all granted' >> /etc/apache2/apache2.conf \
    && echo '</Directory>' >> /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy all source files
COPY . /var/www/html/

# Create symlink for backward compatibility (supports both /api and /backend-php/api)
# Set ownership to www-data
RUN ln -s /var/www/html /var/www/html/backend-php 2>/dev/null || true \
    && chown -R www-data:www-data /var/www/html

# Expose HTTP port for Render and standard environments
EXPOSE 80 10000

# Bind Apache to Render's dynamic $PORT (defaults to 80 if PORT is not set)
CMD ["sh", "-c", "sed -i \"s/80/${PORT:-80}/g\" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf && exec apache2-foreground"]
