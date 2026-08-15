export function decimal(value, precision = 4) {
    return Number.parseFloat(Number(value ?? 0).toFixed(precision));
}

export function normalizeQuantity(value, precision) {
    const quantity = decimal(value, precision);

    if (precision === 0) {
        return Math.trunc(quantity);
    }

    return quantity;
}

export function addOrIncrementItem(items, product) {
    const existing = items.find((item) => item.productId === product.id);

    if (existing) {
        existing.quantity = normalizeQuantity(existing.quantity + 1, existing.unit.precision);
        return [...items];
    }

    return [
        ...items,
        {
            productId: product.id,
            name: product.name,
            sku: product.sku,
            barcode: product.barcode,
            price: decimal(product.price),
            taxRate: decimal(product.tax_rate),
            discountAmount: 0,
            quantity: normalizeQuantity(1, product.unit.precision),
            unit: product.unit,
            trackInventory: product.track_inventory,
            stock: product.stock,
        },
    ];
}

export function previewCartTotals(items) {
    const totals = items.reduce((carry, item) => {
        const lineSubtotal = decimal(item.quantity * item.price);
        const discount = decimal(item.discountAmount);
        const taxable = decimal(Math.max(0, lineSubtotal - discount));
        const tax = decimal(taxable * (decimal(item.taxRate) / 100));
        const lineTotal = decimal(taxable + tax);

        carry.subtotal += lineSubtotal;
        carry.discountTotal += discount;
        carry.taxTotal += tax;
        carry.grandTotal += lineTotal;

        return carry;
    }, {
        subtotal: 0,
        discountTotal: 0,
        taxTotal: 0,
        grandTotal: 0,
    });

    return {
        subtotal: decimal(totals.subtotal),
        discountTotal: decimal(totals.discountTotal),
        taxTotal: decimal(totals.taxTotal),
        grandTotal: decimal(totals.grandTotal),
    };
}

export function paymentSummary(payments, grandTotal) {
    const paid = decimal(payments.reduce((sum, payment) => sum + decimal(payment.amount), 0));
    const remaining = decimal(Math.max(0, decimal(grandTotal) - paid));

    return {
        paid,
        remaining,
    };
}

export function formatMoney(amount, currency = 'MVR') {
    return `${currency} ${decimal(amount, 2).toFixed(2)}`;
}
