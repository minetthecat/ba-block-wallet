<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    header('Allow: PUT');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metoda není povolena.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'));
if (!($body instanceof stdClass)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Požadován JSON objekt.']);
    exit;
}
$data = (array) $body;
$rawId = $data['method_id'] ?? null;
if ((!is_int($rawId) && !is_string($rawId)) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $rawId)
    || (strlen((string) $rawId) === 10 && strcmp((string) $rawId, '4294967295') > 0)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Neplatný method_id.']);
    exit;
}
$id = (string) $rawId;
unset($data['method_id']);
$allowed = ['user_id', 'code', 'name', 'card_number', 'card_expiry', 'card_cvv', 'card_holder_name', 'recipient_account_number', 'recipient_bank_code', 'recipient_iban', 'recipient_bic', 'recipient_bank_name', 'recipient_bank_address', 'recipient_crypto_address', 'can_deposit', 'can_withdraw', 'is_active'];
$nullable = ['card_number', 'card_expiry', 'card_cvv', 'card_holder_name', 'recipient_account_number', 'recipient_bank_code', 'recipient_iban', 'recipient_bic', 'recipient_bank_name', 'recipient_bank_address', 'recipient_crypto_address'];
$textLimits = ['code' => 30, 'name' => 100, 'card_number' => 19, 'card_expiry' => 5, 'card_cvv' => 4, 'card_holder_name' => 100, 'recipient_account_number' => 34, 'recipient_bank_code' => 10, 'recipient_iban' => 34, 'recipient_bic' => 11, 'recipient_bank_name' => 150, 'recipient_bank_address' => 255, 'recipient_crypto_address' => 100];
$clean = [];
$error = null;
foreach ($data as $field => $value) {
    if (!in_array($field, $allowed, true)) { $error = 'Nepovolené pole: ' . $field; break; }
    if ($value === null || (is_string($value) && trim($value) === '')) {
        if (in_array($field, $nullable, true)) { $clean[$field] = null; continue; }
        $error = 'Pole je povinné: ' . $field; break;
    }
    if ($field === 'user_id') {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^(0|[1-9][0-9]{0,9})$/D', (string) $value)
            || (strlen((string) $value) === 10 && strcmp((string) $value, '4294967295') > 0)) {
            $error = 'Neplatný INT UNSIGNED: user_id.'; break;
        }
        $clean[$field] = (string) $value;
    } elseif (array_key_exists($field, $textLimits)) {
        if (!is_string($value) || preg_match('//u', $value) !== 1
            || preg_match_all('/./us', $value, $matches) > $textLimits[$field]) {
            $error = 'Neplatný text nebo délka: ' . $field; break;
        }
        $clean[$field] = $value;
    } else {
        if ((!is_int($value) && !is_string($value)) || !preg_match('/^-?(0|[1-9][0-9]{0,2})$/D', (string) $value)
            || (int) $value < -128 || (int) $value > 127) {
            $error = 'Neplatný TINYINT (-128 až 127): ' . $field; break;
        }
        $clean[$field] = (int) $value;
    }
}
if ($error === null && !$clean) $error = 'Chybí data ke změně.';
if ($error !== null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $error]);
    exit;
}

try {
    require_once __DIR__ . '/../../services/database.php';
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT 1 FROM `payment_method` WHERE `method_id` = ? FOR UPDATE");
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Platební metoda nenalezena.']);
        exit;
    }
    $sets = implode(', ', array_map(function ($field) { return "`$field` = ?"; }, array_keys($clean)));
    $values = array_values($clean);
    $values[] = $id;
    $stmt = $pdo->prepare("UPDATE `payment_method` SET $sets WHERE `method_id` = ?");
    $stmt->execute($values);
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Platební metoda změněna.']);
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
