<?php
function handle_wallet($name, $method, $d) {
    $u = current_user(); $uid = $u['user_id'];
    if ($name === 'wallets') return rows('SELECT * FROM wallet WHERE user_id = ? ORDER BY wallet_id', array($uid));
    if ($name === 'deposit' || $name === 'withdraw') {
        $cur = currency($d); $amount = amount_value($d);
        $methodId = id_value($d, 'method_id');
        return atomic(function() use ($uid, $cur, $amount, $name, $methodId) {
            $pm = found(one('SELECT * FROM payment_method WHERE method_id = ? AND user_id = ? FOR UPDATE', array($methodId, $uid)));
            if (!(int)$pm['is_active'] || !(int)$pm[$name === 'deposit' ? 'can_deposit' : 'can_withdraw']) fail('Platební metoda není dostupná.', 409);
            $tx = wallet_move($uid, $cur, $amount, $name);
            query('UPDATE `transaction` SET method_id = ?, status = ? WHERE transaction_id = ?', array($methodId, $name === 'withdraw' ? 'pending' : 'completed', $tx['transaction_id']));
            return one('SELECT *, CAST(transaction_id AS CHAR) AS transaction_id FROM `transaction` WHERE transaction_id = ?', array($tx['transaction_id']));
        });
    }
    if ($method === 'POST') {
        $cur = currency($d);
        query('INSERT INTO wallet (user_id, currency) VALUES (?, ?)', array($uid, $cur));
        return one('SELECT * FROM wallet WHERE wallet_id = ?', array(db()->lastInsertId()));
    }
    $id = id_value($d, 'wallet_id');
    if ($method === 'GET') return found(one('SELECT * FROM wallet WHERE wallet_id = ? AND user_id = ?', array($id, $uid)));
    $cur = $method === 'PUT' ? currency($d) : null;
    return atomic(function() use ($method, $id, $uid, $cur) {
        $w = found(one('SELECT * FROM wallet WHERE wallet_id = ? AND user_id = ? FOR UPDATE', array($id, $uid)));
        if ($method === 'PUT' && $cur === $w['currency']) return $w;
        // Avoid losing balances, changing historical currency, or broken FK links.
        if ((float)$w['balance'] !== 0.0 || one('SELECT transaction_id FROM `transaction` WHERE wallet_id = ? LIMIT 1', array($id))) fail('Lze upravit nebo smazat jen prázdnou peněženku bez historie.', 409);
        if ($method === 'DELETE') { query('DELETE FROM wallet WHERE wallet_id = ?', array($id)); return null; }
        query('UPDATE wallet SET currency = ? WHERE wallet_id = ?', array($cur, $id));
        return one('SELECT * FROM wallet WHERE wallet_id = ?', array($id));
    });
}
