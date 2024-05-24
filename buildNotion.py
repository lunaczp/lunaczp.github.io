import json
import os
import sys

import requests


class Node(object):
    def __init__(self, id, title):
        self.id = id
        self.title = title
        self.children = []

    def add_child(self, obj):
        self.children.append(obj)

    def to_dict(self):
        return {
            "id": self.id,
            "title": self.title,
            "children": [child.to_dict() for child in self.children],
        }


class NotionBuilder(object):
    def __init__(self, secret):
        self.secret = secret
        self.pageCount = 0

    def buildNode(self, pageId, title):
        self.pageCount += 1
        print(f"Building, count: {self.pageCount}, {title}")

        node = Node(pageId, title)
        pages = self.getSubPages(pageId)
        if pages is None or len(pages) == 0:
            return node

        for page in pages:
            child = self.buildNode(page["id"], page["title"])
            node.add_child(child)
        return node

    def getSubPages(self, pageId):
        api = f"https://api.notion.com/v1/blocks/{pageId}/children?page_size=1000"
        header = {
            "Notion-Version": "2022-06-28",
            "Authorization": f"Bearer {self.secret}",
        }

        response = requests.get(api, headers=header)
        if response.status_code != 200:
            print(f"Error: {response.status_code}, {response.text}")
            return None

        data = response.json()
        if data.get("results") is None:
            return None

        pages = []
        for block in data["results"]:
            if block["type"] == "child_page":
                pages.append(
                    {
                        "id": block["id"],
                        "title": block["child_page"]["title"],
                        "has_children": block["has_children"],
                    }
                )
                #print(f"Found subpage: {block['child_page']['title']}, {block['id']}")

        return pages


if __name__ == "__main__":
    secret = os.getenv("NOTION_API_KEY")
    if secret is None:
        print("Please set the NOTION_API_KEY environment variable")
        exit(1)
    if len(sys.argv) < 3:
        print("Usage: python buildNotion.py <pageId> <title>")
        exit(1)

    pageId = sys.argv[1]
    title = sys.argv[2]

    b = NotionBuilder(secret)
    node = b.buildNode(pageId, title)
    print(json.dumps(node.to_dict()))
