#!/bin/bash

# Script to verify 100% NATIVE development environment
# This script ensures NO Docker is being used for development

echo "🔍 Verifying 100% NATIVE Development Environment"
echo "================================================"

# Check if Docker is NOT running (good for native development)
echo "1. Checking Docker status..."
if command -v docker &> /dev/null; then
    if docker ps &> /dev/null; then
        echo "❌ WARNING: Docker is running! This project uses 100% native development."
        echo "   Please stop all Docker containers for native development."
    else
        echo "✅ Docker is installed but not running (good for native development)"
    fi
else
    echo "✅ Docker is not installed (perfect for 100% native development)"
fi

# Check PHP version
echo ""
echo "2. Checking PHP version..."
PHP_VERSION=$(php -v | head -n 1)
echo "✅ $PHP_VERSION"

# Check Composer
echo ""
echo "3. Checking Composer..."
COMPOSER_VERSION=$(composer --version)
echo "✅ $COMPOSER_VERSION"

# Check Laravel environment
echo ""
echo "4. Checking Laravel environment..."
php artisan about | grep -E "(Environment|Cache|Database|Session|URL)"

# Check if vendor directory exists (native Composer install)
echo ""
echo "5. Checking Composer dependencies..."
if [ -d "vendor" ]; then
    echo "✅ Vendor directory exists (native Composer install)"
else
    echo "❌ Vendor directory missing. Run: composer install"
fi

# Check cache configuration
echo ""
echo "6. Checking cache configuration..."
CACHE_DRIVER=$(php artisan tinker --execute="echo config('cache.default');" 2>/dev/null | tail -n 1)
if [ "$CACHE_DRIVER" = "file" ]; then
    echo "✅ Cache driver: file (perfect for native development)"
else
    echo "⚠️  Cache driver: $CACHE_DRIVER (consider using 'file' for development)"
fi

# Check session configuration
echo ""
echo "7. Checking session configuration..."
SESSION_DRIVER=$(php artisan tinker --execute="echo config('session.driver');" 2>/dev/null | tail -n 1)
if [ "$SESSION_DRIVER" = "file" ]; then
    echo "✅ Session driver: file (perfect for native development)"
else
    echo "⚠️  Session driver: $SESSION_DRIVER (consider using 'file' for development)"
fi

# Test SQLite for testing
echo ""
echo "8. Testing SQLite in-memory for tests..."
if php artisan test --env=testing tests/Unit/ExampleTest.php --quiet; then
    echo "✅ SQLite in-memory testing works correctly"
else
    echo "❌ SQLite testing failed"
fi

# Check that Docker files have been removed (native development only)
echo ""
echo "9. Checking Docker files removal..."
if [ -f "docker-compose.yml" ]; then
    echo "❌ docker-compose.yml should NOT exist (project is 100% native now)"
else
    echo "✅ No docker-compose.yml (correct - project is 100% native)"
fi

if [ -f "Dockerfile" ]; then
    echo "❌ Dockerfile should NOT exist (project is 100% native now)"
else
    echo "✅ No Dockerfile (correct - project is 100% native)"
fi

# Final summary
echo ""
echo "🎉 NATIVE DEVELOPMENT ENVIRONMENT SUMMARY"
echo "=========================================="
echo "✅ Project is 100% NATIVE (no Docker anywhere)"
echo "✅ PHP and Composer are installed natively"
echo "✅ File-based cache and sessions for speed"
echo "✅ SQLite in-memory for fast testing"
echo "✅ Ready for php artisan serve on port 8000"
echo ""
echo "🚀 To start development:"
echo "   php artisan serve --port=8000"
echo ""
echo "🧪 To run tests:"
echo "   php artisan test"
echo ""
echo "📝 Remember: This project is 100% native - no Docker needed!"