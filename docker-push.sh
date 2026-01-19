#!/bin/bash

# ============================================
# Docker Hub Push Script for When2Meet
# ============================================

set -e  # Exit on error

# Configuration
DOCKERHUB_USERNAME="${DOCKERHUB_USERNAME:-your-dockerhub-username}"
IMAGE_NAME="when2meet"
VERSION="${1:-latest}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}==================================${NC}"
echo -e "${GREEN}Docker Hub Push Script${NC}"
echo -e "${GREEN}==================================${NC}"
echo ""

# Check if Docker Hub username is set
if [ "$DOCKERHUB_USERNAME" = "your-dockerhub-username" ]; then
    echo -e "${RED}Error: Please set DOCKERHUB_USERNAME environment variable${NC}"
    echo "Usage: DOCKERHUB_USERNAME=your-username ./docker-push.sh [version]"
    exit 1
fi

# Full image names
LOCAL_IMAGE="${IMAGE_NAME}:${VERSION}"
REMOTE_IMAGE="${DOCKERHUB_USERNAME}/${IMAGE_NAME}:${VERSION}"
REMOTE_IMAGE_LATEST="${DOCKERHUB_USERNAME}/${IMAGE_NAME}:latest"

echo -e "${YELLOW}Building Docker image...${NC}"
echo "Target: production"
echo "Version: ${VERSION}"
echo ""

# Build the image
docker compose build --no-cache app

# Get the built image ID
BUILT_IMAGE=$(docker images --filter "reference=when2meet-app" --format "{{.Repository}}:{{.Tag}}" | head -1)

if [ -z "$BUILT_IMAGE" ]; then
    echo -e "${RED}Error: Failed to build image${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Build successful: ${BUILT_IMAGE}${NC}"
echo ""

# Tag the image
echo -e "${YELLOW}Tagging images...${NC}"
docker tag "${BUILT_IMAGE}" "${REMOTE_IMAGE}"
docker tag "${BUILT_IMAGE}" "${REMOTE_IMAGE_LATEST}"

echo -e "${GREEN}✓ Tagged: ${REMOTE_IMAGE}${NC}"
echo -e "${GREEN}✓ Tagged: ${REMOTE_IMAGE_LATEST}${NC}"
echo ""

# Push to Docker Hub
echo -e "${YELLOW}Pushing to Docker Hub...${NC}"
docker push "${REMOTE_IMAGE}"
docker push "${REMOTE_IMAGE_LATEST}"

echo ""
echo -e "${GREEN}==================================${NC}"
echo -e "${GREEN}✓ Successfully pushed to Docker Hub!${NC}"
echo -e "${GREEN}==================================${NC}"
echo ""
echo "Images pushed:"
echo "  - ${REMOTE_IMAGE}"
echo "  - ${REMOTE_IMAGE_LATEST}"
echo ""
echo "Pull command:"
echo "  docker pull ${REMOTE_IMAGE}"
echo ""
