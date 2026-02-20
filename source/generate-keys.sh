#!/bin/bash
set -e

mkdir -p ./keys

openssl genrsa -out ./keys/repo-signing.key 4096
openssl rsa -in ./keys/repo-signing.key -pubout -out ./keys/repo-signing.pub

chown www-data:www-data ./keys/repo-signing.key ./keys/repo-signing.pub

chmod 600 ./keys/repo-signing.key
chmod 644 ./keys/repo-signing.pub

echo "✓ Keys generated in ./keys/"
