#!/bin/bash

## 
echo Do not use this, it corrupts files
exit;

find . \( -name "*.php" -or -name "*.inc" \) -exec ./scripts/convert_tabs.php {} \;
