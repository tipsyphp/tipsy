#!/bin/bash

# Tipsy Baseline Setup Script
# This script sets up the baseline environment for Tipsy framework testing

set -e

echo "🚀 Setting up Tipsy Baseline Environment..."

# Check Docker availability
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is required but not installed."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is required but not installed."
    exit 1
fi

echo "✅ Docker and Docker Compose found"

# Build the Docker image
echo "🔨 Building Docker image..."
docker-compose build

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
if docker-compose run --rm composer; then
    echo "✅ Composer installation successful"
else
    echo "⚠️  Composer installation failed, trying manual installation..."
    docker-compose run --rm --entrypoint="" tipsy-baseline bash -c "cd /var/www/html && ./manual-install.sh"
fi

# Start the services
echo "🌐 Starting services..."
docker-compose up -d tipsy-baseline

# Wait for Apache to start
echo "⏳ Waiting for Apache to start..."
sleep 5

# Test the application
echo "🧪 Testing the application..."

# Test home page
if curl -s http://localhost:8080 | grep -q "Hello world"; then
    echo "✅ Home page is working"
else
    echo "❌ Home page test failed"
    docker-compose logs tipsy-baseline
    exit 1
fi

# Test info page
if curl -s http://localhost:8080/info | grep -q "phpinfo"; then
    echo "✅ PHP info page is working"
else
    echo "❌ PHP info page test failed"
fi

# Test custom route
if curl -s http://localhost:8080/test | grep -q "Test route is working"; then
    echo "✅ Test route is working"
else
    echo "❌ Test route failed"
fi

# Test 404 handling
if curl -s http://localhost:8080/nonexistent | grep -q "404"; then
    echo "✅ 404 handling is working"
else
    echo "❌ 404 handling test failed"
fi

echo ""
echo "🎉 Tipsy Baseline Environment is ready!"
echo ""
echo "📍 Access Points:"
echo "   • Main App: http://localhost:8080"
echo "   • PHP Info: http://localhost:8080/info"
echo "   • Test Route: http://localhost:8080/test"
echo ""
echo "📊 Commands:"
echo "   • View logs: docker-compose logs -f tipsy-baseline"
echo "   • Stop: docker-compose down"
echo "   • Restart: docker-compose restart"
echo ""
echo "📈 Next steps:"
echo "   1. Run performance benchmarks"
echo "   2. Test all Tipsy features"
echo "   3. Document baseline metrics"