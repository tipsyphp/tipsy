#!/bin/bash

# Tipsy Baseline Performance Testing Script
# Tests the performance characteristics of the original Tipsy framework

set -e

echo "🔥 Starting Tipsy Baseline Performance Tests..."

# Check if the application is running
if ! curl -s http://localhost:8080 > /dev/null; then
    echo "❌ Application is not running. Please run 'docker-compose up -d' first."
    exit 1
fi

echo "✅ Application is responding"

# Install Apache Bench if not available
if ! command -v ab &> /dev/null; then
    echo "⚠️  Apache Bench (ab) not found. Installing via homebrew..."
    if command -v brew &> /dev/null; then
        brew install apache-bench
    else
        echo "❌ Please install Apache Bench (ab) to run performance tests"
        exit 1
    fi
fi

# Performance test configuration
CONCURRENT_USERS=10
TOTAL_REQUESTS=1000
TEST_URLS=(
    "http://localhost:8080/"
    "http://localhost:8080/test"
    "http://localhost:8080/di"
    "http://localhost:8080/params/123"
    "http://localhost:8080/service"
)

echo ""
echo "📊 Performance Test Configuration:"
echo "   • Concurrent Users: $CONCURRENT_USERS"
echo "   • Total Requests: $TOTAL_REQUESTS per endpoint"
echo "   • Test Endpoints: ${#TEST_URLS[@]}"
echo ""

# Create results directory
mkdir -p results
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RESULTS_FILE="results/baseline_performance_$TIMESTAMP.txt"

echo "🚀 Starting performance tests..." | tee "$RESULTS_FILE"
echo "Timestamp: $(date)" | tee -a "$RESULTS_FILE"
echo "PHP Version: $(docker-compose exec -T tipsy-baseline php -v | head -1)" | tee -a "$RESULTS_FILE"
echo "" | tee -a "$RESULTS_FILE"

# Run tests for each URL
for url in "${TEST_URLS[@]}"; do
    endpoint=$(echo "$url" | sed 's|http://localhost:8080||' | sed 's|^$|/|')
    echo "Testing endpoint: $endpoint" | tee -a "$RESULTS_FILE"
    echo "URL: $url" | tee -a "$RESULTS_FILE"
    echo "----------------------------------------" | tee -a "$RESULTS_FILE"
    
    # Run Apache Bench test
    ab -n "$TOTAL_REQUESTS" -c "$CONCURRENT_USERS" -g "results/baseline_${endpoint//\//_}.tsv" "$url" | tee -a "$RESULTS_FILE"
    
    echo "" | tee -a "$RESULTS_FILE"
    echo "========================================" | tee -a "$RESULTS_FILE"
    echo "" | tee -a "$RESULTS_FILE"
done

# Memory usage test
echo "🧠 Memory Usage Test:" | tee -a "$RESULTS_FILE"
echo "Container memory stats:" | tee -a "$RESULTS_FILE"
docker stats --no-stream tipsy-baseline_tipsy-baseline_1 2>/dev/null || docker stats --no-stream tipsy-baseline-tipsy-baseline-1 2>/dev/null | tee -a "$RESULTS_FILE"

# Extract key metrics
echo "" | tee -a "$RESULTS_FILE"
echo "📈 Summary Metrics:" | tee -a "$RESULTS_FILE"
echo "===================" | tee -a "$RESULTS_FILE"

grep "Requests per second" "$RESULTS_FILE" | while read line; do
    echo "$line"
done | tee -a "results/summary_$TIMESTAMP.txt"

grep "Time per request" "$RESULTS_FILE" | while read line; do
    echo "$line"  
done | tee -a "results/summary_$TIMESTAMP.txt"

echo ""
echo "✅ Performance tests completed!"
echo "📁 Results saved to: $RESULTS_FILE"
echo "📊 Summary: results/summary_$TIMESTAMP.txt"
echo ""
echo "🔍 Key metrics to analyze:"
echo "   • Requests per second (baseline throughput)"
echo "   • Time per request (response time)"
echo "   • Memory usage (container overhead)"
echo "   • Failed requests (error rate)"