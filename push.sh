#!/bin/bash

git add .
echo "Enter commit name -->"
read commit
git commit -m "$commit"
git push origin main
echo "Operation successful"