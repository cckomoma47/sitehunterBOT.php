# Dockerfile
FROM php:8.1-cli

WORKDIR /usr/src/app

# Copy your PHP files
COPY . .

# Expose port (Render requires this)
EXPOSE 8080

# Start your bot (using a built-in PHP server for webhook)
CMD ["php", "-S", "0.0.0.0:8080", "sitehunterBOT.php"]
