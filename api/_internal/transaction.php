<?php
function handle_transaction($name, $method, $d) {
    $u = current_user(); $args = array($u['user_id']);
    if ($name === 'transaction' && $method === 'DELETE') {
        $id = id_value($d, 'transaction_id'); $uid = $u['user_id'];
        return atomic(function() use ($id, $uid) {
            $tx = found(one('SELECT *, CAST(transaction_id AS CHAR) AS transaction_id FROM `transaction` WHERE user_id = ? AND transaction_id = ? FOR UPDATE', array($uid, $id)));
            if ($tx['type'] !== 'withdraw' || $tx['status'] !== 'pending') fail('Zrušit lze pouze čekající výběr.', 409);
            $w = found(one('SELECT * FROM wallet WHERE wallet_id = ? AND user_id = ? FOR UPDATE', array($tx['wallet_id'], $uid)));
            if (!one('SELECT wallet_id FROM wallet WHERE wallet_id = ? AND balance <= 9999999999999999.99 - CAST(? AS DECIMAL(18,2))', array($w['wallet_id'], $tx['amount']))) fail('Překročen maximální zůstatek.', 409);
            query('UPDATE wallet SET balance = balance + CAST(? AS DECIMAL(18,2)) WHERE wallet_id = ?', array($tx['amount'], $w['wallet_id']));
            query("UPDATE `transaction` SET status = 'cancelled' WHERE transaction_id = ?", array($id));
            return one('SELECT * FROM wallet WHERE wallet_id = ?', array($w['wallet_id']));
        });
    }
    if ($name === 'transaction') { $args[] = id_value($d, 'transaction_id'); return found(one('SELECT *, CAST(transaction_id AS CHAR) AS transaction_id FROM `transaction` WHERE user_id = ? AND transaction_id = ?', $args)); }
    $where = 'user_id = ?';
    if (isset($d['status'])) { $where .= ' AND status = ?'; $args[] = enum_value($d, 'status', array('pending','completed','cancelled')); }
    if (isset($d['type'])) { $where .= ' AND type = ?'; $args[] = enum_value($d, 'type', array('deposit','withdraw','bet','win','bonus')); }
    if (isset($d['currency'])) { $where .= ' AND currency = ?'; $args[] = currency($d); }
    if (isset($d['wallet_id'])) { $where .= ' AND wallet_id = ?'; $args[] = id_value($d, 'wallet_id'); }
    foreach (array('from','to') as $key) if (isset($d[$key])) {
        $date = date_value($d[$key], $key);
        $where .= $key === 'from' ? ' AND created_at >= ?' : ' AND created_at < DATE_ADD(?, INTERVAL 1 DAY)';
        $args[] = $date;
    }
    if (isset($d['from'], $d['to']) && $d['from'] > $d['to']) fail('Datum od musí být před datem do.');
    return paged('SELECT *, CAST(transaction_id AS CHAR) AS transaction_id FROM `transaction` WHERE ' . $where . ' ORDER BY `transaction`.transaction_id DESC', $args, $d);
}
