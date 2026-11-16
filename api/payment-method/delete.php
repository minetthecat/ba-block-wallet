<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    header('Allow: DELETE');
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
if (array_diff(array_keys($data), ['method_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nepovolené parametry.']);
    exit;
}
$rawId = $data['method_id'] ?? null;
if ((!is_int($rawId) && !is_string($rawId)) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $rawId)
    || (strlen((string) $rawId) === 10 && strcmp((string) $rawId, '4294967295') > 0)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Neplatný method_id.']);
    exit;
}
$id = (string) $rawId;
unset($data['method_id']);

try {
    require_once __DIR__ . '/../../services/database.php';
    $stmt = $pdo->prepare("DELETE FROM `payment_method` WHERE `method_id` = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Platební metoda nenalezena.']);
        exit;
    }
    echo json_encode(['success' => true, 'message' => 'Platební metoda smazána.']);
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
