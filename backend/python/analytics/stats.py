"""Pure calculation functions for sales and store analytics (no database or web code here)."""
from collections import defaultdict
from datetime import date, datetime, timedelta


def summarize(data, days):
    """
    data: dict containing:
      - rows: sales line items
      - movements: inventory movements
      - returns: return headers
      - purchase_orders: PO headers
      - products: product list with stock & cost
    """
    rows = data.get("rows", [])
    movements = data.get("movements", [])
    returns = data.get("returns", [])
    purchase_orders = data.get("purchase_orders", [])
    products_data = data.get("products", [])

    # Sales metrics
    sales = {}
    products = defaultdict(lambda: {"quantity": 0, "revenue": 0.0, "cost": 0.0, "category": "Uncategorized"})
    categories = defaultdict(float)
    daily = defaultdict(lambda: {"revenue": 0.0, "transactions": set()})
    payment_methods = defaultdict(float)
    items_sold = 0
    total_discounts = 0.0
    total_taxes = 0.0

    for r in rows:
        sale_id = r["sale_id"]
        qty = int(r["quantity"])
        sub = float(r["subtotal"])
        cost_price = float(r.get("cost_price") or 0.0)
        p_name = r["product_name"]
        cat_name = r.get("category") or "Uncategorized"
        p_method = r.get("payment_method") or "Cash"

        items_sold += qty
        sales[sale_id] = {
            "total_amount": float(r["total_amount"]),
            "discount_amount": float(r.get("discount_amount") or 0.0),
            "tax_amount": float(r.get("tax_amount") or 0.0),
            "payment_method": p_method,
            "day": r["day"]
        }

        products[p_name]["quantity"] += qty
        products[p_name]["revenue"] += sub
        products[p_name]["cost"] += cost_price * qty
        products[p_name]["category"] = cat_name

        categories[cat_name] += sub
        daily[r["day"]]["revenue"] += sub
        daily[r["day"]]["transactions"].add(sale_id)

    # Unique sale transaction totals
    revenue = round(sum(s["total_amount"] for s in sales.values()), 2)
    count = len(sales)

    for s in sales.values():
        total_discounts += s["discount_amount"]
        total_taxes += s["tax_amount"]
        payment_methods[s["payment_method"]] += s["total_amount"]

    # Top sellers & Slow movers
    all_prod_list = []
    for p_name, p_info in products.items():
        all_prod_list.append({
            "name": p_name,
            "quantity": p_info["quantity"],
            "revenue": round(p_info["revenue"], 2),
            "cost": round(p_info["cost"], 2),
            "estimated_profit": round(p_info["revenue"] - p_info["cost"], 2),
            "category": p_info["category"]
        })

    top_products = sorted(all_prod_list, key=lambda x: (-x["quantity"], -x["revenue"]))[:5]
    slow_movers = sorted(all_prod_list, key=lambda x: (x["quantity"], x["revenue"]))[:5]

    by_cat = sorted([{"category": c, "revenue": round(v, 2)} for c, v in categories.items()], key=lambda x: -x["revenue"])
    by_pm = [{"method": k, "amount": round(v, 2)} for k, v in payment_methods.items()]

    # Stock Valuation
    total_stock_items = 0
    stock_val_retail = 0.0
    stock_val_cost = 0.0
    for p in products_data:
        stk = int(p.get("stock_quantity") or 0)
        prc = float(p.get("price") or 0.0)
        cst = float(p.get("cost_price") or 0.0)
        total_stock_items += stk
        stock_val_retail += stk * prc
        stock_val_cost += stk * cst

    # Total Sales Cost calculation
    total_cost_of_goods = sum(p["cost"] for p in products.values())
    estimated_gross_profit = round(revenue - total_cost_of_goods, 2)

    # Refund Summary
    total_refunded = round(sum(float(r.get("total_refund") or 0.0) for r in returns), 2)
    return_count = len(returns)

    # Purchase Order Summary
    po_total_spent = round(sum(float(po.get("total_cost") or 0.0) for po in purchase_orders if po.get("status") != "Cancelled"), 2)
    po_count = len(purchase_orders)

    # Daily Series
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
        "total_discounts": round(total_discounts, 2),
        "total_taxes": round(total_taxes, 2),
        "total_cost_of_goods": round(total_cost_of_goods, 2),
        "estimated_profit": estimated_gross_profit,
        "stock_valuation": {
            "total_items": total_stock_items,
            "retail_value": round(stock_val_retail, 2),
            "cost_value": round(stock_val_cost, 2)
        },
        "returns_summary": {
            "count": return_count,
            "total_refunded": total_refunded
        },
        "po_summary": {
            "count": po_count,
            "total_spent": po_total_spent
        },
        "top_products": top_products,
        "slow_movers": slow_movers,
        "by_category": by_cat,
        "by_payment_method": by_pm,
        "daily": series,
    }
