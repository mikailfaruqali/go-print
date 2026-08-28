#!/usr/bin/env bash
set -e

TAG="${1:-v1.0.0}"

echo "==================================================="
echo " Building cross-platform release binaries ($TAG)   "
echo "==================================================="

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
BIN_DIR="$ROOT_DIR/bin"
DIST_DIR="$ROOT_DIR/dist"

rm -rf "$DIST_DIR"
mkdir -p "$DIST_DIR"

declare -a TARGETS=(
    "linux/amd64/pdf/pdf_linux_amd64.tar.gz"
    "linux/arm64/pdf/pdf_linux_arm64.tar.gz"
    "darwin/amd64/pdf/pdf_darwin_amd64.tar.gz"
    "darwin/arm64/pdf/pdf_darwin_arm64.tar.gz"
    "windows/amd64/pdf.exe/pdf_windows_amd64.zip"
    "windows/arm64/pdf.exe/pdf_windows_arm64.zip"
)

for target in "${TARGETS[@]}"; do
    IFS="/" read -r GOOS GOARCH BINARY ARCHIVE <<< "$target"
    echo "--> Building $GOOS/$GOARCH..."

    TEMP_DIR="$DIST_DIR/temp_${GOOS}_${GOARCH}"
    mkdir -p "$TEMP_DIR"

    (cd "$BIN_DIR" && CGO_ENABLED=0 GOOS=$GOOS GOARCH=$GOARCH go build -ldflags="-s -w -X main.Version=$TAG" -o "$TEMP_DIR/$BINARY" .)

    if [[ "$ARCHIVE" == *.zip ]]; then
        (cd "$TEMP_DIR" && zip -q -9 "$DIST_DIR/$ARCHIVE" "$BINARY")
    else
        tar -czf "$DIST_DIR/$ARCHIVE" -C "$TEMP_DIR" "$BINARY"
    fi

    rm -rf "$TEMP_DIR"
    echo "    [OK] Created $ARCHIVE"
done

echo ""
echo "==================================================="
echo " All release packages created in ./dist folder:"
ls -lh "$DIST_DIR"
echo "==================================================="
echo "You can now upload the files from ./dist to GitHub Release $TAG manually or using gh CLI:"
echo "  gh release create $TAG ./dist/* --title $TAG --notes \"Release $TAG\""
