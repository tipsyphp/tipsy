#!/bin/bash

echo "🔍 Tipsy Framework Status Check"
echo "==============================="
echo ""

# Check if we're in the right directory
if [ ! -f "README.md" ] || [ ! -d "nextgen" ]; then
    echo "❌ Please run this from the tipsy project root directory"
    exit 1
fi

# Check Docker
echo "🐳 Docker Status:"
if docker info >/dev/null 2>&1; then
    echo "   ✅ Docker is running"
else
    echo "   ❌ Docker is not running"
    echo "   Please start Docker to run demos"
fi
echo ""

# Check containers
echo "📦 Container Status:"
cd nextgen/baseline
if docker-compose ps | grep -q "Up"; then
    echo "   ✅ NextGen containers are running"
    echo "   🌐 Demo available at: http://localhost:8080"
    echo ""
    echo "   📊 Container Details:"
    docker-compose ps
else
    echo "   ⚠️  NextGen containers are not running"
    echo "   Run './demo.sh' to start the demo"
fi
echo ""

# Check for legacy containers
cd ../../legacy/baseline
if [ -f "docker-compose.yml" ] && docker-compose ps 2>/dev/null | grep -q "Up"; then
    echo "   📈 Legacy containers also running on port 8081"
    echo "   🔄 Run './real_comparison.sh' for performance comparison"
else
    echo "   📈 Legacy containers not running (normal)"
fi
echo ""

cd ../../

# Show available commands
echo "🚀 Available Commands:"
echo "   ./demo.sh           - Start NextGen demo"
echo "   ./demo.sh stop      - Stop demo"
echo "   ./demo.sh logs      - View logs"
echo "   ./demo.sh compare   - Performance comparison"
echo "   ./real_comparison.sh - Full legacy vs nextgen test"
echo ""

# Quick framework test if containers are running
if curl -s http://localhost:8080/api/test >/dev/null 2>&1; then
    echo "✅ Quick API Test: PASSED"
    echo "   Framework is responding correctly"
    echo ""
    echo "🎯 Test the demo:"
    echo "   curl http://localhost:8080/api/test | jq"
else
    echo "⚠️  Quick API Test: No response"
    echo "   Start demo with './demo.sh' first"
fi