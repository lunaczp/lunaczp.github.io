import json
import os
import sys


class Json2Md(object):

    def toMd(self, node, level=0):
        if node is None:
            return
        
        if node["type"] == "child_page":
            link = f"[{node['title']}](https://www.notion.so/lunaczp/{node['id'].replace('-', '')})"
            print("  " * level, "-", link)
            nextLevel = level + 1
        else:
            #不打印，则level也不变，保证对齐
            nextLevel = level

        if node.get("children") is None:
            return

        for child in node["children"]:
            self.toMd(child, nextLevel)


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python buildNotion.py <jsonDataFile>")
        exit(1)

    jsonFile = sys.argv[1]
    with open(jsonFile, "r") as f:
        jsonData = json.load(f)

    j = Json2Md()
    j.toMd(jsonData)
