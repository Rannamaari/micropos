import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { addOrIncrementItem, decimal, formatMoney, normalizeQuantity, paymentSummary, previewCartTotals } from '../lib/cart';

function uuid() {
    return crypto.randomUUID();
}

function defaultSalesHistoryFilters() {
    return {
        search: '',
        period: 'today',
        date_from: '',
        date_to: '',
        cashier: '',
        customer: '',
        status: '',
        payment_method: '',
        page: 1,
        per_page: 25,
    };
}

export const usePosStore = defineStore('pos', () => {
    const bootstrap = ref({});
    const items = ref([]);
    const customer = ref(null);
    const searchResults = ref([]);
    const customerResults = ref([]);
    const heldSales = ref([]);
    const saleSearchResults = ref([]);
    const salesHistoryMeta = ref({
        current_page: 1,
        last_page: 1,
        per_page: 25,
        total: 0,
        from: null,
        to: null,
    });
    const salesHistoryFilters = ref(defaultSalesHistoryFilters());
    const resumedSaleId = ref(null);
    const payments = ref([]);
    const loading = ref({
        searchingProducts: false,
        searchingCustomers: false,
        holdingSale: false,
        completingSale: false,
        loadingHeldSales: false,
        loadingSaleLookup: false,
        processingReturn: false,
    });
    const errors = ref([]);
    const notifications = ref([]);
    const paymentModalOpen = ref(false);
    const customerModalOpen = ref(false);
    const heldSalesModalOpen = ref(false);
    const saleLookupModalOpen = ref(false);
    const shortcutModalOpen = ref(false);
    const saleCompleteModal = ref(null);
    const selectedSearchIndex = ref(0);
    const activeSaleLookup = ref(null);
    const searchInputValue = ref('');

    const currency = computed(() => bootstrap.value.company?.currency ?? 'MVR');
    const permissions = computed(() => new Set(bootstrap.value.user?.permissions ?? []));
    const canOverridePrice = computed(() => permissions.value.has('sales.price_override'));
    const canDiscount = computed(() => permissions.value.has('sales.discount'));
    const canCreateCustomer = computed(() => permissions.value.has('customers.create'));
    const canUseCredit = computed(() => permissions.value.has('sales.credit'));
    const canViewSalesHistory = computed(() => permissions.value.has('sales.view'));
    const canReturnSales = computed(() => permissions.value.has('sales.return'));
    const canResumeHeldSales = computed(() => permissions.value.has('sales.resume') || permissions.value.has('sales.hold'));
    const canCancelHeldSales = computed(() => permissions.value.has('sales.cancel_held'));
    const canViewCancelledSales = computed(() => permissions.value.has('sales.view_cancelled'));
    const isResumedSale = computed(() => resumedSaleId.value !== null);
    const hasNamedCustomer = computed(() => Boolean(customer.value && !customer.value.is_walk_in));
    const canHoldSale = computed(() => items.value.length > 0 && hasNamedCustomer.value);
    const totals = computed(() => previewCartTotals(items.value));
    const paymentTotals = computed(() => paymentSummary(payments.value, totals.value.grandTotal));
    const dirty = computed(() => items.value.length > 0);
    const hasSalesHistoryResults = computed(() => saleSearchResults.value.length > 0);

    function hydrate(data) {
        bootstrap.value = data;
        customer.value = data.walk_in_customer;
    }

    function notify(message, type = 'info') {
        notifications.value = [{ id: uuid(), message, type }];
    }

    function setErrors(payload) {
        const messages = [];

        if (typeof payload === 'string') {
            messages.push(payload);
        } else if (Array.isArray(payload)) {
            messages.push(...payload);
        } else if (payload && typeof payload === 'object') {
            Object.values(payload).forEach((value) => {
                if (Array.isArray(value)) {
                    value.forEach((entry) => {
                        if (typeof entry === 'string') {
                            messages.push(entry);
                        } else if (entry?.product_name) {
                            messages.push(`${entry.product_name}: requested ${entry.requested}, available ${entry.available}.`);
                        }
                    });
                }
            });
        }

        errors.value = messages;
    }

    function clearErrors() {
        errors.value = [];
    }

    function setSearchResults(results) {
        searchResults.value = results;
        selectedSearchIndex.value = 0;
    }

    function addProduct(product) {
        items.value = addOrIncrementItem(items.value, product);
        searchInputValue.value = '';
        setSearchResults([]);
        notify(`${product.name} added`, 'success');
    }

    function incrementItem(productId) {
        const line = items.value.find((item) => item.productId === productId);
        if (! line) return;
        line.quantity = normalizeQuantity(line.quantity + 1, line.unit.precision);
    }

    function decrementItem(productId) {
        const line = items.value.find((item) => item.productId === productId);
        if (! line) return;
        const next = normalizeQuantity(line.quantity - 1, line.unit.precision);
        if (next <= 0) {
            removeItem(productId);
            return;
        }
        line.quantity = next;
    }

    function updateItemQuantity(productId, value) {
        const line = items.value.find((item) => item.productId === productId);
        if (! line) return;
        const quantity = normalizeQuantity(value, line.unit.precision);
        if (! Number.isFinite(quantity) || quantity <= 0) return;
        line.quantity = quantity;
    }

    function updateItemPrice(productId, value) {
        if (! canOverridePrice.value) return;
        const line = items.value.find((item) => item.productId === productId);
        if (! line) return;
        line.price = decimal(value);
    }

    function updateItemDiscount(productId, value) {
        if (! canDiscount.value) return;
        const line = items.value.find((item) => item.productId === productId);
        if (! line) return;
        line.discountAmount = decimal(value);
    }

    function removeItem(productId) {
        items.value = items.value.filter((item) => item.productId !== productId);
    }

    function setCustomer(selectedCustomer) {
        customer.value = selectedCustomer;
        customerModalOpen.value = false;
    }

    function resetSale() {
        items.value = [];
        searchResults.value = [];
        customerResults.value = [];
        payments.value = [];
        resumedSaleId.value = null;
        saleCompleteModal.value = null;
        activeSaleLookup.value = null;
        searchInputValue.value = '';
        customer.value = bootstrap.value.walk_in_customer;
        clearErrors();
    }

    function resetSalesHistoryFilters() {
        salesHistoryFilters.value = defaultSalesHistoryFilters();
    }

    function serializeItems() {
        return items.value.map((item) => ({
            product_id: item.productId,
            quantity: item.quantity,
            unit_price: decimal(item.price),
            discount_amount: decimal(item.discountAmount),
        }));
    }

    function salesHistoryParams(page = salesHistoryFilters.value.page) {
        const filters = salesHistoryFilters.value;

        return {
            search: filters.search || undefined,
            period: filters.period,
            date_from: filters.period === 'custom' ? (filters.date_from || undefined) : undefined,
            date_to: filters.period === 'custom' ? (filters.date_to || undefined) : undefined,
            cashier: filters.cashier || undefined,
            customer: filters.customer || undefined,
            status: filters.status || undefined,
            payment_method: filters.payment_method || undefined,
            page,
            per_page: filters.per_page,
        };
    }

    function formatDateTime(value) {
        if (! value) return 'N/A';

        return new Intl.DateTimeFormat(undefined, {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(value));
    }

    function formatStatus(status) {
        if (! status) return '';

        return status
            .split('_')
            .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
            .join(' ');
    }

    async function searchProducts(query) {
        searchInputValue.value = query;

        if (! query.trim()) {
            setSearchResults([]);
            return;
        }

        loading.value.searchingProducts = true;
        clearErrors();

        try {
            const response = await window.axios.get('/pos/api/products/search', {
                params: { q: query },
            });
            setSearchResults(response.data.data);
        } catch (error) {
            setErrors(error.response?.data?.errors ?? 'Unable to search products.');
        } finally {
            loading.value.searchingProducts = false;
        }
    }

    async function scanOrLookup(query) {
        const value = query.trim();

        if (! value) return false;

        clearErrors();

        try {
            const response = await window.axios.get(`/pos/api/products/barcode/${encodeURIComponent(value)}`);
            addProduct(response.data.data);
            return true;
        } catch (error) {
            if (error.response?.status === 404) {
                await searchProducts(value);
                if (searchResults.value.length === 1) {
                    addProduct(searchResults.value[0]);
                    return true;
                }
                setErrors(error.response?.data?.errors ?? 'Barcode not found.');
                return false;
            }

            setErrors(error.response?.data?.errors ?? 'Unable to look up barcode.');
            return false;
        }
    }

    async function searchCustomers(query) {
        loading.value.searchingCustomers = true;
        try {
            const response = await window.axios.get('/pos/api/customers/search', {
                params: { q: query },
            });
            customerResults.value = response.data.data;
        } catch (error) {
            setErrors(error.response?.data?.errors ?? 'Unable to search customers.');
        } finally {
            loading.value.searchingCustomers = false;
        }
    }

    async function createCustomer(payload) {
        const response = await window.axios.post('/pos/api/customers', payload);
        customer.value = response.data.data;
        customerResults.value = [response.data.data];
        customerModalOpen.value = false;
        notify('Customer created', 'success');
    }

    async function loadHeldSales() {
        loading.value.loadingHeldSales = true;
        try {
            const response = await window.axios.get('/pos/api/held-sales');
            heldSales.value = response.data.data;
        } catch (error) {
            setErrors(error.response?.data?.errors ?? 'Unable to load held sales.');
        } finally {
            loading.value.loadingHeldSales = false;
        }
    }

    async function resumeHeldSale(saleId) {
        clearErrors();

        try {
            const response = await window.axios.get(`/pos/api/sales/${saleId}/resume`);
            hydrateSaleResponse(response.data.data, true);
            heldSalesModalOpen.value = false;
            notify(`Held sale ${response.data.data.sale_number} resumed`, 'success');
        } catch (error) {
            setErrors(error.response?.data?.errors ?? error.response?.data?.message ?? 'Unable to resume held sale.');
        }
    }

    function hydrateSaleResponse(sale, resumed = false) {
        resumedSaleId.value = resumed ? sale.id : null;
        customer.value = sale.customer ?? bootstrap.value.walk_in_customer;
        items.value = sale.items.map((item) => ({
            productId: item.product_id,
            name: item.name,
            sku: item.sku,
            barcode: item.barcode,
            price: decimal(item.unit_price),
            taxRate: decimal(item.tax_rate),
            discountAmount: decimal(item.discount_amount),
            quantity: decimal(item.quantity),
            unit: item.unit,
            trackInventory: item.track_inventory,
            stock: null,
        }));
        payments.value = [];
    }

    async function holdSale(notes = '') {
        if (! hasNamedCustomer.value) {
            setErrors(['Select a saved customer before holding a sale.']);
            return;
        }

        loading.value.holdingSale = true;
        clearErrors();

        try {
            const payload = {
                customer_id: customer.value?.id ?? null,
                notes,
                items: serializeItems(),
            };
            const response = resumedSaleId.value
                ? await window.axios.post(`/pos/api/sales/${resumedSaleId.value}/hold`, payload)
                : await window.axios.post('/pos/api/sales/hold', {
                    ...payload,
                    client_transaction_uuid: uuid(),
                });
            resetSale();
            notify(`Held ${response.data.data.sale_number}`, 'success');
        } catch (error) {
            setErrors(error.response?.data?.errors ?? error.response?.data?.message ?? 'Unable to hold sale.');
        } finally {
            loading.value.holdingSale = false;
        }
    }

    async function completeSale(notes = '') {
        loading.value.completingSale = true;
        clearErrors();

        try {
            const payload = {
                customer_id: customer.value?.id ?? null,
                notes,
                items: serializeItems(),
                payments: payments.value.map((payment) => ({
                    payment_method: payment.payment_method,
                    amount: decimal(payment.amount),
                    amount_tendered: payment.amount_tendered ? decimal(payment.amount_tendered) : null,
                    reference: payment.reference || null,
                    notes: payment.notes || null,
                })),
            };

            const response = resumedSaleId.value
                ? await window.axios.post(`/pos/api/sales/${resumedSaleId.value}/complete`, payload)
                : await window.axios.post('/pos/api/sales', {
                    ...payload,
                    client_transaction_uuid: uuid(),
                });

            paymentModalOpen.value = false;
            resetSale();
            saleCompleteModal.value = response.data.data;
            notify(`Sale ${response.data.data.sale_number} complete`, 'success');
        } catch (error) {
            setErrors(error.response?.data?.errors ?? error.response?.data?.message ?? 'Unable to complete sale.');
        } finally {
            loading.value.completingSale = false;
        }
    }

    async function loadSalesHistory(page = 1) {
        loading.value.loadingSaleLookup = true;
        clearErrors();

        try {
            const response = await window.axios.get('/pos/api/sales', {
                params: salesHistoryParams(page),
            });
            saleSearchResults.value = response.data.data;
            salesHistoryMeta.value = response.data.meta;
            salesHistoryFilters.value = {
                ...salesHistoryFilters.value,
                ...response.data.filters,
                page: response.data.meta.current_page,
                per_page: response.data.meta.per_page,
            };
        } catch (error) {
            setErrors(error.response?.data?.errors ?? 'Unable to load sales history.');
        } finally {
            loading.value.loadingSaleLookup = false;
        }
    }

    async function searchSales(query, period = salesHistoryFilters.value.period) {
        salesHistoryFilters.value = {
            ...salesHistoryFilters.value,
            search: query,
            period,
            page: 1,
        };

        await loadSalesHistory(1);
    }

    async function prepareSalesHistory() {
        if (! canViewSalesHistory.value) return;

        activeSaleLookup.value = null;
        saleLookupModalOpen.value = true;

        if (! saleSearchResults.value.length) {
            resetSalesHistoryFilters();
        }

        await loadSalesHistory(1);
    }

    async function loadSale(saleId) {
        loading.value.loadingSaleLookup = true;
        clearErrors();

        try {
            const response = await window.axios.get(`/pos/api/sales/${saleId}`);
            activeSaleLookup.value = response.data.data;
        } catch (error) {
            setErrors(error.response?.data?.errors ?? 'Unable to load sale.');
        } finally {
            loading.value.loadingSaleLookup = false;
        }
    }

    async function processReturn(itemsToReturn, notes = '') {
        if (! activeSaleLookup.value) return;

        loading.value.processingReturn = true;
        clearErrors();

        try {
            const response = await window.axios.post(`/pos/api/sales/${activeSaleLookup.value.id}/returns`, {
                items: itemsToReturn,
                notes,
            });
            activeSaleLookup.value = response.data.data;
            await loadSalesHistory(salesHistoryFilters.value.page);
            notify('Sale return processed', 'success');
        } catch (error) {
            setErrors(error.response?.data?.errors ?? 'Unable to process return.');
        } finally {
            loading.value.processingReturn = false;
        }
    }

    function updateSalesHistoryFilter(key, value) {
        salesHistoryFilters.value = {
            ...salesHistoryFilters.value,
            [key]: value,
        };
    }

    async function applySalesHistoryFilters() {
        salesHistoryFilters.value = {
            ...salesHistoryFilters.value,
            page: 1,
        };

        await loadSalesHistory(1);
    }

    async function clearSalesHistoryFilters() {
        resetSalesHistoryFilters();
        await loadSalesHistory(1);
    }

    async function goToSalesHistoryPage(page) {
        if (page < 1 || page > salesHistoryMeta.value.last_page) return;
        await loadSalesHistory(page);
    }

    function printActiveSale() {
        window.print();
    }

    function addPayment(payment) {
        payments.value.push({
            id: uuid(),
            payment_method: payment.payment_method,
            amount: decimal(payment.amount),
            amount_tendered: payment.amount_tendered ? decimal(payment.amount_tendered) : null,
            reference: payment.reference ?? '',
            notes: payment.notes ?? '',
        });
    }

    function removePayment(paymentId) {
        payments.value = payments.value.filter((payment) => payment.id !== paymentId);
    }

    async function cancelHeldSale(saleId, reason, notes = '') {
        if (! canCancelHeldSales.value) return false;

        clearErrors();

        try {
            await window.axios.post(`/pos/api/sales/${saleId}/cancel-held`, {
                reason,
                notes,
            });
            heldSales.value = heldSales.value.filter((sale) => sale.id !== saleId);
            if (resumedSaleId.value === saleId) {
                resetSale();
            }
            notify('Held sale cancelled', 'success');
            return true;
        } catch (error) {
            setErrors(error.response?.data?.errors ?? error.response?.data?.message ?? 'Unable to cancel held sale.');
            return false;
        }
    }

    return {
        bootstrap,
        items,
        customer,
        searchResults,
        customerResults,
        heldSales,
        saleSearchResults,
        salesHistoryMeta,
        salesHistoryFilters,
        resumedSaleId,
        payments,
        loading,
        errors,
        notifications,
        paymentModalOpen,
        customerModalOpen,
        heldSalesModalOpen,
        saleLookupModalOpen,
        shortcutModalOpen,
        saleCompleteModal,
        selectedSearchIndex,
        activeSaleLookup,
        searchInputValue,
        currency,
        permissions,
        canOverridePrice,
        canDiscount,
        canCreateCustomer,
        canUseCredit,
        canViewSalesHistory,
        canReturnSales,
        canResumeHeldSales,
        canCancelHeldSales,
        canViewCancelledSales,
        isResumedSale,
        hasNamedCustomer,
        canHoldSale,
        totals,
        paymentTotals,
        dirty,
        hasSalesHistoryResults,
        hydrate,
        formatMoney: (amount) => formatMoney(amount, currency.value),
        formatDateTime,
        formatStatus,
        notify,
        setErrors,
        clearErrors,
        setSearchResults,
        addProduct,
        incrementItem,
        decrementItem,
        updateItemQuantity,
        updateItemPrice,
        updateItemDiscount,
        removeItem,
        setCustomer,
        resetSale,
        resetSalesHistoryFilters,
        searchProducts,
        scanOrLookup,
        searchCustomers,
        createCustomer,
        loadHeldSales,
        resumeHeldSale,
        holdSale,
        completeSale,
        loadSalesHistory,
        searchSales,
        prepareSalesHistory,
        loadSale,
        processReturn,
        updateSalesHistoryFilter,
        applySalesHistoryFilters,
        clearSalesHistoryFilters,
        goToSalesHistoryPage,
        printActiveSale,
        addPayment,
        removePayment,
        cancelHeldSale,
    };
});
