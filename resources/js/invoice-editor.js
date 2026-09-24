/**
 * Alpine component behind the invoice line editor.
 *
 * It mirrors App\Support\LineTotals in integer cents so the preview matches
 * what the server will store. The server always recalculates on save; this
 * is only a live preview.
 */
export default ({ rows = [], products = [], customers = [], customerId = '', defaultVatRate = '0' }) => ({
    rows: rows.length ? rows.map((row) => normalise(row)) : [blankRow(defaultVatRate)],
    customerId: customerId ? String(customerId) : '',

    addRow() {
        this.rows.push(blankRow(defaultVatRate));
    },

    removeRow(index) {
        this.rows.splice(index, 1);
        if (this.rows.length === 0) {
            this.addRow();
        }
    },

    productFor(row) {
        return products.find((p) => String(p.id) === String(row.product_id));
    },

    pickProduct(row) {
        const product = this.productFor(row);
        if (!product) {
            return;
        }
        row.description = product.name;
        row.unit_price = product.unit_price;
        row.vat_rate = product.vat_rate;
    },

    discountRate(row) {
        const customer = customers.find((c) => String(c.id) === this.customerId);
        const product = this.productFor(row);
        if (!customer || !product || !product.apply_customer_discount) {
            return 0;
        }
        return parseFloat(customer.discount_rate) || 0;
    },

    line(row) {
        const unit = toCents(row.unit_price);
        const quantity = parseInt(row.quantity, 10) || 0;
        const subtotal = unit * quantity;
        const discount = percentage(subtotal, this.discountRate(row));
        const net = subtotal - discount;
        const tax = percentage(net, parseFloat(row.vat_rate) || 0);

        return { subtotal, discount, net, tax, total: net + tax };
    },

    get totals() {
        return this.rows.reduce(
            (sum, row) => {
                const line = this.line(row);
                Object.keys(sum).forEach((key) => (sum[key] += line[key]));
                return sum;
            },
            { subtotal: 0, discount: 0, net: 0, tax: 0, total: 0 },
        );
    },

    money(cents) {
        return (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
});

function blankRow(vatRate) {
    return { product_id: '', description: '', quantity: 1, free_quantity: 0, unit_price: '', vat_rate: vatRate };
}

function normalise(row) {
    return {
        product_id: row.product_id ?? '',
        description: row.description ?? '',
        quantity: row.quantity ?? 1,
        free_quantity: row.free_quantity ?? 0,
        unit_price: row.unit_price ?? '',
        vat_rate: row.vat_rate ?? '0',
    };
}

function toCents(value) {
    return Math.round((parseFloat(value) || 0) * 100);
}

// Half-up rounding to the cent, like Money::percentage() on the server.
function percentage(cents, rate) {
    return Math.round((cents * Math.round(rate * 100)) / 10000);
}
