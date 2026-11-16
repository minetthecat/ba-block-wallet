<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metoda není povolena.']);
    exit;
}

$data = $_GET;
if (array_diff(array_keys($data), ['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nepovolené parametry.']);
    exit;
}
$rawId = $data['user_id'] ?? null;
if ((!is_int($rawId) && !is_string($rawId)) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $rawId)
    || (strlen((string) $rawId) === 10 && strcmp((string) $rawId, '4294967295') > 0)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Neplatný user_id.']);
    exit;
}
$id = (string) $rawId;

try {
    require_once __DIR__ . '/../../services/database.php';
    $stmt = $pdo->prepare("SELECT CAST(`method_id` AS CHAR) AS `method_id`, CAST(`user_id` AS CHAR) AS `user_id`, `code`, `name`, RIGHT(`card_number`, 4) AS `card_last4`, `card_expiry`, `card_holder_name`, `recipient_account_number`, `recipient_bank_code`, `recipient_iban`, `recipient_bic`, `recipient_bank_name`, `recipient_bank_address`, `recipient_crypto_address`, `can_deposit`, `can_withdraw`, `is_active` FROM `payment_method` WHERE `user_id` = ? ORDER BY `method_id` DESC");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Platební metody načteny.', 'data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('payment-method API: ' . $e->getMessage());
    $code = (int) ($e->errorInfo[1] ?? $e->getCode());
    if ($code === 1062) {
        http_response_code(409);
        $message = 'Platební metoda s tímto code již existuje.';
    } elseif ($code === 1452) {
        http_response_code(404);
        $message = 'Odkazovaný uživatel neexistuje.';
    } elseif ($code === 1451) {
        http_response_code(409);
        $message = 'Platební metodu používají jiné záznamy.';
    } elseif ($code === 1146) {
        http_response_code(500);
        $database = null;
        try { $database = $pdo->query('SELECT DATABASE()')->fetchColumn(); }
        catch (PDOException $ignored) {}
        $message = 'V připojené DB' . ($database ? ' (' . $database . ')' : '')
            . ' chybí tabulka payment_method. Zkontroluj services/database.php; dodaný export používá betarena.';
    } elseif ($code === 1049) {
        http_response_code(500);
        $message = 'Databáze nastavená v services/database.php neexistuje. Dodaný export používá betarena.';
    } elseif ($code === 1045 || $code === 1698) {
        http_response_code(500);
        $message = 'Přístup k databázi byl odmítnut. Zkontroluj přihlašovací údaje v services/database.php.';
    } elseif ($code === 2002 || $code === 2003) {
        http_response_code(500);
        $message = 'Databázový server není dostupný. Zkontroluj hostitele a port v services/database.php.';
    } elseif ($code === 1054) {
        http_response_code(500);
        $message = 'Struktura payment_method neodpovídá dodanému SQL: chybí sloupec. Zkontroluj připojenou databázi.';
    } else {
        http_response_code(500);
        $message = 'Chyba databáze (kód ' . $code . '). Podrobnosti jsou v PHP error logu.';
    }
    echo json_encode(['success' => false, 'message' => $message]);
}
