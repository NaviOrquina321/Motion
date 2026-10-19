"""Pure calculation functions for sales analytics (no database or web code here)."""
from collections import defaultdict
from datetime import date, timedelta


def summarize(rows, days):
    """rows: one dict per sold line item (see php/reports/summary.php)."""
    sales = {}                                   # sale_id -> total (each sale counted once)
    products = defaultdict(lambda: {"quantity": 0, "revenue": 0.0})
    categories = defaultdict(float)
    daily = defaultdict(lambda: {"revenue": 0.0, "transactions": set()})
    items_sold = 0

    for r in rows:
        qty, sub = int(r["quantity"]), float(r["subtotal"])
        items_sold += qty
        sales[r["sale_id"]] = float(r["total_amount"])
        products[r["product_name"]]["quantity"] += qty
        products[r["product_name"]]["revenue"] += sub
        categories[r["category"]] += sub
        daily[r["day"]]["revenue"] += sub
        daily[r["day"]]["transactions"].add(r["sale_id"])

    revenue = round(sum(sales.values()), 2)
    count = len(sales)

    top = sorted(products.items(), key=lambda kv: (-kv[1]["quantity"], -kv[1]["revenue"]))[:5]
    by_cat = sorted(categories.items(), key=lambda kv: -kv[1])

    # One entry for every day in the period, including days with no sales
    today = date.today()
    series = []
    for i in range(days - 1, -1, -1):
        d = (today - timedelta(days=i)).isoformat()
        info = daily.get(d)
        series.append({
            "day": d,
            "revenue": round(info["revenue"], 2) if info else 0.0,
            "transactions": len(info["transactions"]) if info else 0,
        })

    return {
        "days": days,
        "total_revenue": revenue,
        "transactions": count,
        "average_sale": round(revenue / count, 2) if count else 0.0,
        "items_sold": items_sold,
        "top_products": [
            {"name": n, "quantity": v["quantity"], "revenue": round(v["revenue"], 2)} for n, v in top
        ],
        "by_category": [{"category": c, "revenue": round(v, 2)} for c, v in by_cat],
        "daily": series,
    }
