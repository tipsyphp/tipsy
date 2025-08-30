#!/bin/bash

# Real Tipsy Framework Performance Comparison Script
# Actually starts containers and runs real benchmarks

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LEGACY_DIR="$SCRIPT_DIR/legacy/baseline"
NEXTGEN_DIR="$SCRIPT_DIR/nextgen/baseline"

echo -e "${BLUE}Starting REAL performance comparison...${NC}"

# Check dependencies
if ! command -v wrk &> /dev/null; then
    echo -e "${RED}ERROR: wrk not found. Install with: brew install wrk${NC}"
    exit 1
fi

if ! command -v docker &> /dev/null; then
    echo -e "${RED}ERROR: docker not found${NC}"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}ERROR: docker-compose not found${NC}"
    exit 1
fi

# Function to wait for service
wait_for_service() {
    local url=$1
    local name=$2
    local max_attempts=30
    local attempt=1
    
    echo -e "${YELLOW}Waiting for $name...${NC}"
    
    while [ $attempt -le $max_attempts ]; do
        if curl -f -s "$url" > /dev/null 2>&1; then
            echo -e "${GREEN}✓ $name is ready${NC}"
            return 0
        fi
        sleep 1
        attempt=$((attempt + 1))
    done
    
    echo -e "${RED}✗ $name failed to start${NC}"
    return 1
}

# Stop any existing containers
echo -e "${YELLOW}Stopping existing containers...${NC}"
cd "$LEGACY_DIR" && docker-compose down 2>/dev/null || true
cd "$NEXTGEN_DIR" && docker-compose down 2>/dev/null || true

# Start Legacy (PHP 5.6) on port 8081
echo -e "${BLUE}Starting Legacy PHP 5.6 container...${NC}"
cd "$LEGACY_DIR"
# Modify port to avoid conflicts
sed 's/8080:80/8081:80/' docker-compose.yml > docker-compose-8081.yml
docker-compose -f docker-compose-8081.yml up -d
wait_for_service "http://localhost:8081" "Legacy PHP 5.6"

# Start NextGen (PHP 8.3) on port 8080  
echo -e "${BLUE}Starting NextGen PHP 8.3 container...${NC}"
cd "$NEXTGEN_DIR"
docker-compose up -d
wait_for_service "http://localhost:8080" "NextGen PHP 8.3"

# Verify both are responding
echo -e "${BLUE}Verifying services...${NC}"
echo "Legacy response:"
curl -s http://localhost:8081/ | head -50
echo ""
echo "NextGen response:"
curl -s http://localhost:8080/ | head -50
echo ""

# Run actual benchmarks
echo -e "${BLUE}Running REAL benchmarks (30 seconds each)...${NC}"

echo -e "${YELLOW}Testing Legacy PHP 5.6...${NC}"
wrk -t4 -c10 -d30s --latency http://localhost:8081/ > legacy_results.txt
echo "Legacy results:"
cat legacy_results.txt
echo ""

echo -e "${YELLOW}Testing NextGen PHP 8.3...${NC}"
wrk -t4 -c10 -d30s --latency http://localhost:8080/ > nextgen_results.txt
echo "NextGen results:"
cat nextgen_results.txt
echo ""

# Extract key metrics
legacy_rps=$(grep "Requests/sec:" legacy_results.txt | awk '{print $2}')
nextgen_rps=$(grep "Requests/sec:" nextgen_results.txt | awk '{print $2}')

legacy_latency=$(grep "Latency" legacy_results.txt | head -1 | awk '{print $2}')
nextgen_latency=$(grep "Latency" nextgen_results.txt | head -1 | awk '{print $2}')

# Calculate improvement
if command -v bc >/dev/null 2>&1; then
    improvement=$(echo "scale=2; $nextgen_rps / $legacy_rps" | bc -l)
else
    improvement="N/A"
fi

# Display results
echo -e "${GREEN}=== REAL COMPARISON RESULTS ===${NC}"
echo -e "Legacy (PHP 5.6):   ${YELLOW}$legacy_rps req/sec${NC} @ ${YELLOW}$legacy_latency${NC}"
echo -e "NextGen (PHP 8.3):  ${GREEN}$nextgen_rps req/sec${NC} @ ${GREEN}$nextgen_latency${NC}"
echo -e "Improvement:        ${GREEN}${improvement}x faster${NC}"

# Save results
cat > comparison_results.txt << EOF
Tipsy Framework Real Performance Comparison
Generated: $(date)

Legacy (PHP 5.6): $legacy_rps req/sec, $legacy_latency latency
NextGen (PHP 8.3): $nextgen_rps req/sec, $nextgen_latency latency
Improvement: ${improvement}x

Full Legacy Results:
$(cat legacy_results.txt)

Full NextGen Results:
$(cat nextgen_results.txt)
EOF

echo -e "${GREEN}Results saved to comparison_results.txt${NC}"

# Cleanup
echo -e "${YELLOW}Stopping containers...${NC}"
cd "$LEGACY_DIR" && docker-compose -f docker-compose-8081.yml down
cd "$NEXTGEN_DIR" && docker-compose down

echo -e "${GREEN}Comparison complete!${NC}"