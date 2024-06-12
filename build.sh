#!/bin/bash
if [ -z "$NOTION_API_KEY" ]; then
  echo "Please set NOTION_API_KEY"
  exit
fi

python buildNotion.py  6fb016ac39c74a4aa2c16c71b4f78945 "Zhang Peng's Wiki"
python json2md.py notion.json > README.md