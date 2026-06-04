<?php
session_start();
include  'config.php';
include 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$bookingId = intval($_GET['booking_id'] ?? 0);
$hours = floatval($_GET['hours'] ?? 0);

if (!$bookingId) {
    die('Invalid booking reference.');
}

$stmt = $pdo->prepare("
    SELECT b.id, b.status,
           p.title, p.price,
           u.name AS client_name,
           u.email AS client_email
    FROM bookings b
    JOIN posts p ON b.post_id = p.id
    JOIN users u ON b.user_id = u.id
    WHERE b.id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die('Booking not found.');
}

if ($booking['status'] !== 'completed') {
    die('Payment only allowed after completion.');
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$url = PF_SANDBOX
    ? 'https://sandbox.payfast.co.za/eng/process'
    : 'https://www.payfast.co.za/eng/process';

// Fee constants
$FUEL_COST = 100;
$PLATFORM_FEE = 50;

$amount = $booking['price'];
if ($hours > 0) {
    $amount = round($booking['price'] * $hours, 2);
}

// Add fees
$amount = round($amount + $FUEL_COST + $PLATFORM_FEE, 2);
$nameParts = explode(' ', trim($booking['client_name']), 2);

$data = [
    'merchant_id'   => PF_MERCHANT_ID,
    'merchant_key'  => PF_MERCHANT_KEY,
    'return_url'    => $protocol . '://' . $host . $baseUrl . '/index.php?payment=success',
    'cancel_url'    => $protocol . '://' . $host . $baseUrl . '/index.php?payment=cancelled',
    'notify_url'    => $protocol . '://' . $host . $baseUrl . '/payfast.php',

    'name_first'    => $nameParts[0] ?? '',
    'name_last'     => $nameParts[1] ?? '',
    'email_address' => $booking['client_email'],

    'm_payment_id'  => (string)$bookingId,
    'amount'        => number_format($amount, 2, '.', ''),
    'item_name'     => $hours > 0 ? 'Booking #' . $bookingId . ' (' . $hours . 'h)' : 'Booking #' . $bookingId,
];

$pfString = [];
foreach ($data as $key => $val) {
    if ($val !== '') {
        $pfString[] = $key . '=' . urlencode(trim($val));
    }
}

$signatureString = implode('&', $pfString);

if (!empty(PF_PASSPHRASE)) {
    $signatureString .= '&passphrase=' . urlencode(PF_PASSPHRASE);
}

$data['signature'] = md5($signatureString);
?>
<!DOCTYPE html>
<html>
<head>  
<title>Pay Booking</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
.container { background: #fff; padding: 28px; border-radius: 16px; width: min(500px, 92vw); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
h1 { margin: 0 0 24px 0; font-size: 24px; color: #2c2c2c; }
.summary { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-bottom: 22px; }
.summary div { margin-bottom: 10px; font-size: 15px; }
.summary div:last-child { margin-bottom: 0; }
.summary b { font-weight: 600; color: #2c2c2c; }
.amount { font-size: 20px; font-weight: 700; color: #6366f1; margin-top: 14px; padding-top: 14px; border-top: 1px solid #d1d5db; }
form { margin-top: 22px; }
button { width: 100%; padding: 14px 16px; background: #6366f1; color: #fff; border: none; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; }
button:hover { background: #6366f1; }
</style>
</head>
<body>
<div class="container">
  <h1>Finalize Payment</h1>
  
  <div class="summary">
    <div><b>Booking:</b> #<?php echo $bookingId; ?></div>
    <div><b>Service:</b> <?php echo htmlspecialchars($booking['title']); ?></div>
    <div><b>Rate:</b> R <?php echo number_format($booking['price'], 2); ?>/hour</div>
    <?php if ($hours > 0): ?>
      <div><b>Hours:</b> <?php echo htmlspecialchars($hours); ?></div>
    <?php endif; ?>
        <div style="border-top: 1px solid #d1d5db; margin-top: 12px; padding-top: 12px;">
            <div><b>Service Total:</b> R <?php echo number_format($booking['price'] * ($hours > 0 ? $hours : 1), 2); ?></div>
            <div><b>Fuel Cost:</b> R <?php echo number_format($FUEL_COST, 2); ?></div>
            <div><b>Platform Fee:</b> R <?php echo number_format($PLATFORM_FEE, 2); ?></div>
        </div>
    <div class="amount">Total: R <?php echo number_format($amount, 2); ?></div>
  </div>

  <form action="<?php echo $url; ?>" method="post">
    <?php foreach ($data as $key => $value): ?>
      <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>">
    <?php endforeach; ?>
    <button type="submit">💳 Pay with PayFast</button>
  </form>
</div>
</body>
</html>