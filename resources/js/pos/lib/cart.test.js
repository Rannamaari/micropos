import { addOrIncrementItem, paymentSummary, previewCartTotals } from './cart';

describe('cart helpers', () => {
    it('adds scanned product and increments repeated scans', () => {
        const product = {
            id: 'product-1',
            name: 'Coca-Cola 500ml',
            sku: 'COKE-500',
            barcode: '123',
            price: '15.00',
            tax_rate: '8.00',
            track_inventory: true,
            stock: '46.0000',
            unit: { precision: 0, short_name: 'pcs', name: 'Piece' },
        };

        const once = addOrIncrementItem([], product);
        const twice = addOrIncrementItem(once, product);

        expect(twice).toHaveLength(1);
        expect(twice[0].quantity).toBe(2);
    });

    it('calculates cart total preview', () => {
        const totals = previewCartTotals([
            {
                quantity: 2,
                price: 15,
                discountAmount: 0,
                taxRate: 8,
            },
        ]);

        expect(totals.subtotal).toBe(30);
        expect(totals.taxTotal).toBe(2.4);
        expect(totals.grandTotal).toBe(32.4);
    });

    it('calculates payment remaining correctly', () => {
        const summary = paymentSummary([
            { amount: 600 },
            { amount: 400 },
        ], 1000);

        expect(summary.paid).toBe(1000);
        expect(summary.remaining).toBe(0);
    });
});
