#!/usr/bin/env bash
set -e

echo "==================================================="
echo "Running pdf test suite on Linux"
echo "==================================================="

if [ -f "./pdf" ]; then
    BIN="./pdf"
elif [ -f "../pdf" ]; then
    BIN="../pdf"
else
    echo "Error: pdf binary not found. Please build it first:"
    echo "go build -ldflags=\"-s -w\" -o pdf ."
    exit 1
fi

"$BIN" \
  --content "test/content.html" \
  --header "test/header.html" \
  --footer "test/footer.html" \
  --watermark "test/watermark.html" \
  --watermark-opacity 1.0 \
  --output "test/output.pdf" \
  --paper A4 \
  --margin-top 5mm \
  --margin-bottom 5mm \
  --margin-left 5mm \
  --margin-right 5mm \
  --header-height 25mm \
  --footer-height 15mm \
  --orientation portrait

echo ""
echo "==================================================="
echo "SUCCESS Output PDF generated at test/output.pdf"
echo "==================================================="
