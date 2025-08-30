#!/bin/bash

# Handle command line arguments
COMMAND=${1:-start}

case $COMMAND in
    "stop")
        echo "🛑 Stopping Tipsy Demo..."
        cd nextgen/baseline
        docker-compose down
        echo "✅ Demo stopped."
        exit 0
        ;;
    "logs")
        echo "📋 Container Logs:"
        cd nextgen/baseline
        docker-compose logs -f
        exit 0
        ;;
    "restart")
        echo "🔄 Restarting Tipsy Demo..."
        cd nextgen/baseline
        docker-compose down
        docker-compose up -d
        echo "✅ Demo restarted."
        echo "🌐 Available at: http://localhost:8080"
        exit 0
        ;;
    "compare")
        echo "📊 Running Performance Comparison..."
        ./real_comparison.sh
        exit 0
        ;;
    "start"|"")
        # Continue with start logic below
        ;;
    *)
        echo "❓ Unknown command: $COMMAND"
        echo ""
        echo "📋 Available commands:"
        echo "   ./demo.sh           - Start the demo"
        echo "   ./demo.sh stop      - Stop the demo"
        echo "   ./demo.sh logs      - View container logs"
        echo "   ./demo.sh restart   - Restart containers"
        echo "   ./demo.sh compare   - Run performance comparison"
        exit 1
        ;;
esac

echo "🚀 Tipsy Framework v2.0 - Modernized Demo"
echo "========================================="
echo ""

# Check if Docker is running
if ! docker info >/dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

echo "🐳 Starting NextGen (PHP 8.3+) Demo Environment..."
echo ""

# Navigate to nextgen baseline
cd nextgen/baseline

# Stop any existing containers
docker-compose down --remove-orphans 2>/dev/null

# Start the modernized framework
echo "Starting containers..."
if docker-compose up -d; then
    echo ""
    echo "✅ NextGen Tipsy Framework is now running!"
    echo ""
    echo "🌐 Demo URLs:"
    echo "   Main Demo:     http://localhost:8080"
    echo "   Hello World:   http://localhost:8080/"
    echo "   JSON API:      http://localhost:8080/api/test"
    echo ""
    echo "📊 Container Status:"
    docker-compose ps
    echo ""
    echo "📋 Available Commands:"
    echo "   ./demo.sh stop     - Stop the demo"
    echo "   ./demo.sh logs     - View container logs"
    echo "   ./demo.sh restart  - Restart containers"
    echo "   ./demo.sh compare  - Run performance comparison"
    echo ""
    echo "🎯 To see the performance comparison vs legacy PHP 5.6:"
    echo "   cd ../../ && ./real_comparison.sh"
    echo ""
    
    # Try to open browser (macOS)
    if command -v open >/dev/null 2>&1; then
        echo "🌟 Opening browser..."
        sleep 2
        open http://localhost:8080
    fi
    
else
    echo "❌ Failed to start containers. Check Docker setup."
    exit 1
fi