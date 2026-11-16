/* Wallet pages — use the shared API helpers loaded by index.php. */
(function () {
    'use strict';
    async function init() {
        const mode = location.pathname.replace(/\/+$/, '').split('/').pop();
        if (!['deposit', 'withdraw', 'method', 'wait'].includes(mode)) return;
        const saved = document.getElementById('saved-methods');
        const queue = document.getElementById('wait-body');
        if (!saved && !queue) return;
        let methods = [], wallets = [], user;
        const node = (tag, text, cls) => {
            const el = document.createElement(tag);
            if (text !== undefined) el.textContent = text;
            if (cls) el.className = cls;
            return el;
        };
        const money = (value, currency) => Number(value).toLocaleString('cs-CZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + currency;
        function kind(m) {
            return m.card_last4 ? 'card' : m.recipient_crypto_address ? 'bsc' : 'bank';
        }
        function description(m) {
            if (kind(m) === 'card') return '•••• ' + m.card_last4 + ' · ' + (m.card_expiry || '');
            if (kind(m) === 'bsc') return m.recipient_crypto_address + ' · BSC';
            return [m.recipient_bank_name, m.recipient_iban || [m.recipient_account_number, m.recipient_bank_code].filter(Boolean).join('/')].filter(Boolean).join(' · ');
        }
        function balance(form) {
            const el = form.querySelector('[data-balance]');
            if (el) el.textContent = 'Dostupný zůstatek: ' + money((wallets.find(w => w.currency === form.elements.currency.value) || {}).balance || 0, form.elements.currency.value);
        }
        function kind(method) {
            if (method.recipient_crypto_address) return 'bsc';
            if (method.card_last4 || method.card_holder_name || method.card_expiry) return 'card';
            return 'bank';
        }
        function renderMethods() {
            saved.replaceChildren();
            const available = methods.filter(m => mode === 'method' || (Number(m.is_active) === 1 && Number(m[mode === 'deposit' ? 'can_deposit' : 'can_withdraw']) === 1));
            if (!available.length) saved.append(node('p', 'Nemáte žádnou dostupnou platební metodu.'));
            available.forEach(m => {
                const details = node('details', undefined, 'wallet-method-collapse');
                const summary = node('summary', undefined, 'wallet-method-summary');
                summary.append(node('span', kind(m).toUpperCase(), 'wallet-method-icon'));
                const text = node('span', undefined, 'wallet-method-summary-text');
                text.append(node('span', m.name, 'wallet-method-summary-title'), node('span', description(m), 'wallet-method-summary-description'));
                summary.append(text); details.append(summary);
                if (mode === 'method') {
                    const info = node('div', undefined, 'wallet-deposit-form');
                    const fields = { card_holder_name: 'Držitel karty', card_last4: 'Poslední čtyři číslice karty', card_expiry: 'Platnost', recipient_account_number: 'Číslo účtu', recipient_bank_code: 'Kód banky', recipient_iban: 'IBAN', recipient_bic: 'BIC / SWIFT', recipient_bank_name: 'Banka', recipient_bank_address: 'Adresa banky', recipient_crypto_address: 'Krypto adresa' };
                    Object.entries(fields).forEach(([key, label]) => { if (m[key]) info.append(node('p', label + ': ' + m[key])); });
                    info.append(node('p', 'Aktivní: ' + (Number(m.is_active) === 1 ? 'ano' : 'ne') + ' · Vklady: ' + (Number(m.can_deposit) === 1 ? 'ano' : 'ne') + ' · Výběry: ' + (Number(m.can_withdraw) === 1 ? 'ano' : 'ne')));
                    details.append(info);
                } else {
                    const form = node('form', undefined, 'wallet-deposit-form');
                    form.dataset.methodId = m.method_id;
                    const amount = node('label', 'Částka', 'wallet-deposit-field');
                    const input = node('input'); Object.assign(input, { name: 'amount', type: 'number', min: '0.01', step: '0.01', required: true }); amount.append(input);
                    const currency = node('label', 'Měna', 'wallet-deposit-field');
                    const select = node('select'); select.name = 'currency';
                    wallets.filter(w => kind(m) === 'bsc' ? w.currency === 'BSC' : w.currency !== 'BSC').forEach(w => { const opt = node('option', w.currency); opt.value = w.currency; select.append(opt); });
                    currency.append(select);
                    const btn = node('button', mode === 'deposit' ? 'Vložit' : 'Vybrat', 'wallet-deposit-submit'); btn.type = 'submit'; btn.disabled = !select.options.length;
                    const status = node('p', select.options.length ? '' : 'Pro tuto metodu nemáte peněženku.', 'wallet-deposit-message'); status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
                    form.append(amount, currency, btn, status);
                    if (mode === 'withdraw') { const b = node('p', undefined, 'wallet-deposit-message'); b.dataset.balance = ''; form.append(b); balance(form); }
                    details.append(form);
                }
                saved.append(details);
            });
        }
        async function refresh() {
            const result = await Promise.all([getPaymentMethods({ user_id: user.user_id }), getWallets()]);
            methods = result[0].data; wallets = result[1].data;
            if (saved) renderMethods();
        }
        async function renderQueue() {
            const txs = [];
            for (let page = 1; ; page++) {
                const result = await getTransactions({ type: 'withdraw', status: 'pending', page, limit: 100 });
                txs.push(...result.data);
                if (result.data.length < 100) break;
            }
            queue.replaceChildren();
            txs.forEach(tx => {
                const row = node('tr');
                const pm = methods.find(m => String(m.method_id) === String(tx.method_id));
                [tx.created_at, 'Výběr', pm ? pm.name : 'Metoda #' + (tx.method_id || '—'), '−' + money(tx.amount, tx.currency), 'Čeká na schválení'].forEach(value => row.append(node('td', value)));
                const action = node('td'); const btn = node('button', 'Vzít zpět', 'wallet-btn-danger wallet-btn-small'); btn.type = 'button';
                btn.addEventListener('click', async () => {
                    if (!window.confirm('Opravdu chcete vzít výběr zpět?')) return;
                    btn.disabled = true;
                    try {
                        await sendApiRequest('/api/transaction/transaction', { transaction_id: String(tx.transaction_id) }, 'DELETE');
                        window.alert('Výběr byl zrušen a částka vrácena do peněženky.');
                        await refresh(); await renderQueue();
                    } catch (error) { window.alert(error.message); } finally { btn.disabled = false; }
                });
                action.append(btn); row.append(action); queue.append(row);
            });
            document.getElementById('wait-count').textContent = '(' + txs.length + ')';
            document.getElementById('wait-empty').hidden = txs.length > 0;
            document.querySelector('.wallet-history-scroll').hidden = txs.length === 0;
        }
        const forms = document.querySelectorAll('[data-add-method], [data-new-method="true"]');
        forms.forEach(form => {
            // The method is saved first; submit a payment separately using its saved entry.
            form.querySelectorAll('[name="amount"], [name="currency"], [name="save_method"], [name="card_cvv"]').forEach(el => el.closest('label')?.remove());
            form.querySelector('button[type="submit"]').textContent = 'Přidat metodu';
            const msg = form.querySelector('[role="status"]'); if (msg) msg.textContent = '';
        });
        document.addEventListener('change', event => { if (event.target.name === 'currency' && event.target.form) balance(event.target.form); });
        document.addEventListener('submit', async event => {
            const form = event.target;
            if (!form.matches('[data-add-method], [data-new-method="true"], [data-method-id]')) return;
            event.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            if (btn.disabled || !form.reportValidity()) return;
            const status = form.querySelector('[role="status"]');
            btn.disabled = true;
            try {
                if (!user) throw new Error('Údaje uživatele nejsou načteny. Obnovte stránku.');
                if (form.dataset.methodId) {
                    const data = { method_id: form.dataset.methodId, amount: form.elements.amount.value, currency: form.elements.currency.value };
                    await (mode === 'deposit' ? depositToWallet(data) : withdrawFromWallet(data));
                    form.reset();
                    window.alert(mode === 'deposit' ? 'Vklad byl připsán do peněženky.' : 'Výběr čeká na schválení. Částka byla odečtena z peněženky.');
                } else {
                    const data = Object.fromEntries(new FormData(form));
                    const type = form.dataset.addMethod || form.dataset.method;
                    if (type === 'bank' && !data.recipient_iban?.trim() && !(data.recipient_account_number?.trim() && data.recipient_bank_code?.trim())) throw new Error('Vyplňte IBAN nebo číslo účtu a kód banky.');
                    if (data.card_number) data.card_number = data.card_number.replace(/\s/g, '');
                    if (data.recipient_iban) data.recipient_iban = data.recipient_iban.replace(/\s/g, '');
                    Object.assign(data, { user_id: user.user_id, code: type + '_' + Array.from(crypto.getRandomValues(new Uint8Array(10)), value => value.toString(16).padStart(2, '0')).join(''), can_deposit: 1, can_withdraw: 1, is_active: 1 });
                    await createPaymentMethod(data);
                    form.reset(); status.textContent = 'Platební metoda byla uložena. Nyní ji můžete vybrat v seznamu.';
                }
                await refresh();
            } catch (error) { status.textContent = error.message; } finally { btn.disabled = false; }
        });
        try {
            user = (await getAccount()).data;
            await refresh();
            if (queue) await renderQueue();
        } catch (error) {
            if (saved) saved.replaceChildren(node('p', error.message));
            if (queue) { const row = node('tr'); const cell = node('td', error.message); cell.colSpan = 6; row.append(cell); queue.replaceChildren(row); }
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
