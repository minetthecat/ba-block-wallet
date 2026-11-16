/* PAYMENT METHOD — load after services/api.js */

async function getPaymentMethod(data) {
    return sendApiRequest('/api/payment-method/get', data, 'GET');
}

async function createPaymentMethod(data) {
    return sendApiRequest('/api/payment-method/post', data, 'POST');
}

async function updatePaymentMethod(data) {
    return sendApiRequest('/api/payment-method/put', data, 'PUT');
}

async function deletePaymentMethod(data) {
    return sendApiRequest('/api/payment-method/delete', data, 'DELETE');
}


async function getPaymentMethods(data) {
    return sendApiRequest('/api/payment-method/list.php', data, 'GET');
}

if (typeof window !== 'undefined') {
    window.PaymentMethodApi = {
        version: '20261115-wallet',
        list: getPaymentMethods,
        get: getPaymentMethod,
        create: createPaymentMethod,
        update: updatePaymentMethod,
        remove: deletePaymentMethod
    };
}

// getPaymentMethods({ user_id: 3 });