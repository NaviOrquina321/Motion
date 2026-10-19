"""
Sales report service.

Input : JSON  {"days": 30, "rows": [...], "returns": [...], "purchase_orders": [...], "products": [...]}   (from stdin, or a file path given as argument)
Output: JSON summary printed to stdout

PHP runs this script and talks to it only through JSON, so Python never needs
database access. You can also test it by hand:
    python3 sales_summary.py sample.json
"""
import json
import os
import sys

sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), ".."))
from analytics.stats import summarize  # noqa: E402


def main():
    try:
        raw = open(sys.argv[1], encoding="utf-8").read() if len(sys.argv) > 1 else sys.stdin.read()
        data = json.loads(raw)
        days = int(data.get("days", 30))
        print(json.dumps(summarize(data, days)))
    except Exception as exc:  # report the problem to PHP via stderr + non-zero exit code
        print(f"sales_summary error: {exc}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
