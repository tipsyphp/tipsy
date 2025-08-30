#!/bin/bash

# Tipsy Framework Performance Validation Script
# Compares legacy PHP 5.6 vs modern PHP 8.3+ performance

set -e

# Configuration
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RESULTS_DIR="./results"
NEXTGEN_URL="http://tipsy-nextgen/app-hello/"
LEGACY_URL="http://tipsy-legacy/app-hello/"
WARMUP_REQUESTS=100
BENCHMARK_DURATION="30s"
BENCHMARK_CONNECTIONS=10
BENCHMARK_THREADS=4

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Create results directory
mkdir -p "$RESULTS_DIR"

echo -e "${BLUE}� Tipsy Framework Performance Validation${NC}"
echo -e "${BLUE}==========================================${NC}"
echo "Timestamp: $(date)"
echo "Results will be saved to: $RESULTS_DIR"
echo ""

# Function to wait for service to be ready
wait_for_service() {
    local url=$1
    local name=$2
    local max_attempts=30
    local attempt=1
    
    echo -e "${YELLOW}⏳ Waiting for $name to be ready...${NC}"
    
    while [ $attempt -le $max_attempts ]; do
        if curl -f -s "$url" > /dev/null 2>&1; then
            echo -e "${GREEN}✅ $name is ready!${NC}"
            return 0
        fi
        
        echo "Attempt $attempt/$max_attempts failed, retrying in 2 seconds..."
        sleep 2
        attempt=$((attempt + 1))
    done
    
    echo -e "${RED}❌ $name failed to start after $max_attempts attempts${NC}"
    return 1
}

# Function to run warmup requests
warmup_service() {
    local url=$1
    local name=$2
    
    echo -e "${YELLOW}🔥 Warming up $name...${NC}"
    
    for i in $(seq 1 $WARMUP_REQUESTS); do
        curl -s "$url" > /dev/null || true
        if [ $((i % 20)) -eq 0 ]; then
            echo "Warmup progress: $i/$WARMUP_REQUESTS requests"
        fi
    done
    
    echo -e "${GREEN}✅ $name warmed up with $WARMUP_REQUESTS requests${NC}"
}

# Function to run performance benchmark
run_benchmark() {
    local url=$1
    local name=$2
    local output_file=$3
    
    echo -e "${BLUE}📊 Running benchmark for $name...${NC}"
    echo "URL: $url"
    echo "Duration: $BENCHMARK_DURATION"
    echo "Connections: $BENCHMARK_CONNECTIONS"
    echo "Threads: $BENCHMARK_THREADS"
    echo ""
    
    # Run wrk benchmark
    wrk -t$BENCHMARK_THREADS -c$BENCHMARK_CONNECTIONS -d$BENCHMARK_DURATION \
        --timeout 10s \
        --latency \
        "$url" | tee "$output_file"
    
    echo ""
}

# Function to extract metrics from wrk output
extract_metrics() {
    local file=$1
    local requests_per_sec=$(grep "Requests/sec:" "$file" | awk '{print $2}')
    local transfer_per_sec=$(grep "Transfer/sec:" "$file" | awk '{print $2}')
    local avg_latency=$(grep "Latency" "$file" | head -1 | awk '{print $2}')
    local max_latency=$(grep "Latency" "$file" | head -1 | awk '{print $4}')
    
    echo "requests_per_sec:$requests_per_sec"
    echo "transfer_per_sec:$transfer_per_sec"
    echo "avg_latency:$avg_latency"
    echo "max_latency:$max_latency"
}

# Function to get system info
get_system_info() {
    echo "# System Information" > "$RESULTS_DIR/system_info_$TIMESTAMP.txt"
    echo "Date: $(date)" >> "$RESULTS_DIR/system_info_$TIMESTAMP.txt"
    echo "Docker Version: $(docker --version)" >> "$RESULTS_DIR/system_info_$TIMESTAMP.txt"
    echo "Docker Compose Version: $(docker-compose --version)" >> "$RESULTS_DIR/system_info_$TIMESTAMP.txt"
    echo "" >> "$RESULTS_DIR/system_info_$TIMESTAMP.txt"
    
    # Get container info
    echo "# Container Information" >> "$RESULTS_DIR/system_info_$TIMESTAMP.txt"
    docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Status}}" >> "$RESULTS_DIR/system_info_$TIMESTAMP.txt"
    echo "" >> "$RESULTS_DIR/system_info_$TIMESTAMP.txt"
}

# Main execution
main() {
    echo -e "${BLUE}� Starting Docker services...${NC}"
    
    # Start nextgen service
    docker-compose up -d tipsy-nextgen
    
    # Start legacy service if available
    if [ "$1" = "--with-legacy" ]; then
        echo "Starting legacy service for comparison..."
        docker-compose --profile legacy up -d tipsy-legacy
        COMPARE_LEGACY=true
    else
        echo "Running nextgen-only benchmark (use --with-legacy to compare)"
        COMPARE_LEGACY=false
    fi
    
    echo ""
    
    # Wait for services to be ready
    wait_for_service "$NEXTGEN_URL" "Tipsy NextGen (PHP 8.3+)"
    
    if [ "$COMPARE_LEGACY" = true ]; then
        wait_for_service "$LEGACY_URL" "Tipsy Legacy (PHP 5.6)"
    fi
    
    echo ""
    
    # Collect system information
    get_system_info
    
    # Warmup services
    warmup_service "$NEXTGEN_URL" "Tipsy NextGen"
    
    if [ "$COMPARE_LEGACY" = true ]; then
        warmup_service "$LEGACY_URL" "Tipsy Legacy"
    fi
    
    echo ""
    
    # Run benchmarks
    echo -e "${BLUE}🏃‍♂️ Starting performance benchmarks...${NC}"
    echo ""
    
    # Benchmark NextGen
    run_benchmark "$NEXTGEN_URL" "Tipsy NextGen (PHP 8.3+)" "$RESULTS_DIR/nextgen_benchmark_$TIMESTAMP.txt"
    
    if [ "$COMPARE_LEGACY" = true ]; then
        # Benchmark Legacy
        run_benchmark "$LEGACY_URL" "Tipsy Legacy (PHP 5.6)" "$RESULTS_DIR/legacy_benchmark_$TIMESTAMP.txt"
    fi
    
    echo -e "${BLUE}📈 Generating performance summary...${NC}"
    
    # Generate summary
    SUMMARY_FILE="$RESULTS_DIR/performance_summary_$TIMESTAMP.txt"
    
    echo "# Tipsy Framework Performance Comparison" > "$SUMMARY_FILE"
    echo "Generated: $(date)" >> "$SUMMARY_FILE"
    echo "Test Duration: $BENCHMARK_DURATION" >> "$SUMMARY_FILE"
    echo "Connections: $BENCHMARK_CONNECTIONS" >> "$SUMMARY_FILE"
    echo "Threads: $BENCHMARK_THREADS" >> "$SUMMARY_FILE"
    echo "" >> "$SUMMARY_FILE"
    
    # Extract and compare metrics
    if [ -f "$RESULTS_DIR/nextgen_benchmark_$TIMESTAMP.txt" ]; then
        echo "## NextGen (PHP 8.3+) Results" >> "$SUMMARY_FILE"
        NEXTGEN_METRICS=$(extract_metrics "$RESULTS_DIR/nextgen_benchmark_$TIMESTAMP.txt")
        echo "$NEXTGEN_METRICS" >> "$SUMMARY_FILE"
        echo "" >> "$SUMMARY_FILE"
        
        NEXTGEN_RPS=$(echo "$NEXTGEN_METRICS" | grep "requests_per_sec" | cut -d: -f2)
    fi
    
    if [ "$COMPARE_LEGACY" = true ] && [ -f "$RESULTS_DIR/legacy_benchmark_$TIMESTAMP.txt" ]; then
        echo "## Legacy (PHP 5.6) Results" >> "$SUMMARY_FILE"
        LEGACY_METRICS=$(extract_metrics "$RESULTS_DIR/legacy_benchmark_$TIMESTAMP.txt")
        echo "$LEGACY_METRICS" >> "$SUMMARY_FILE"
        echo "" >> "$SUMMARY_FILE"
        
        LEGACY_RPS=$(echo "$LEGACY_METRICS" | grep "requests_per_sec" | cut -d: -f2)
        
        # Calculate improvement (if bc is available)
        if command -v bc >/dev/null 2>&1 && [ -n "$NEXTGEN_RPS" ] && [ -n "$LEGACY_RPS" ]; then
            IMPROVEMENT=$(echo "scale=2; $NEXTGEN_RPS / $LEGACY_RPS" | bc -l)
            echo "## Performance Improvement" >> "$SUMMARY_FILE"
            echo "NextGen vs Legacy: ${IMPROVEMENT}x faster" >> "$SUMMARY_FILE"
            echo "" >> "$SUMMARY_FILE"
            
            echo -e "${GREEN}🎯 Performance Results:${NC}"
            echo -e "  NextGen (PHP 8.3+): ${GREEN}$NEXTGEN_RPS req/sec${NC}"
            echo -e "  Legacy (PHP 5.6):   ${YELLOW}$LEGACY_RPS req/sec${NC}"
            echo -e "  Improvement:         ${GREEN}${IMPROVEMENT}x faster${NC}"
        fi
    else
        echo -e "${GREEN}🎯 NextGen Performance:${NC}"
        echo -e "  Requests/sec: ${GREEN}$NEXTGEN_RPS${NC}"
    fi
    
    echo ""
    echo -e "${GREEN}✅ Benchmark completed successfully!${NC}"
    echo -e "� Results saved to: ${BLUE}$SUMMARY_FILE${NC}"
    echo ""
    echo -e "${YELLOW}� Quick Commands:${NC}"
    echo "  View summary:    cat $SUMMARY_FILE"
    echo "  View logs:       docker-compose logs tipsy-nextgen"
    echo "  Stop services:   docker-compose down"
    echo "  Clean up:        docker-compose down -v --rmi all"
}

# Check if wrk is available
check_deps() {
    if ! command -v wrk &> /dev/null; then
        echo -e "${YELLOW}⚠️  Installing wrk benchmark tool...${NC}"
        if command -v apk &> /dev/null; then
            apk add --no-cache wrk curl
        elif command -v apt-get &> /dev/null; then
            apt-get update && apt-get install -y wrk curl
        elif command -v brew &> /dev/null; then
            brew install wrk
        else
            echo -e "${RED}❌ Cannot install wrk - unsupported package manager${NC}"
            exit 1
        fi
    fi
    
    if ! command -v curl &> /dev/null; then
        echo -e "${YELLOW}⚠️  Installing curl...${NC}"
        if command -v apk &> /dev/null; then
            apk add --no-cache curl
        elif command -v apt-get &> /dev/null; then
            apt-get update && apt-get install -y curl
        fi
    fi
}

# Run checks and main function
check_deps
main "$@"