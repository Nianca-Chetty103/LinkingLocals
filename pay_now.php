<?php
session_start();
require 'config.php';
include 'database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$bookingId = intval($_GET['booking_id'] ?? 0);
if (!$bookingId) {
    die('Invalid booking reference.');
}

$stmt = $pdo->prepare("
    SELECT b.id, b.status, b.user_id AS client_id, b.post_id,
           p.title, p.price,
           u.name AS client_name
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
    die('Booking not ready for payment.');
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$url = PF_SANDBOX
    ? 'https://sandbox.payfast.co.za/eng/process'
    : 'https://www.payfast.co.za/eng/process';

$data = [
    'merchant_id'   => PF_MERCHANT_ID,
    'merchant_key'  => PF_MERCHANT_KEY,
    'return_url'    => $protocol . '://' . $host . $baseUrl . '/index.php?payment=success',
    'cancel_url'    => $protocol . '://' . $host . $baseUrl . '/index.php?payment=cancelled',
    'notify_url'    => $protocol . '://' . $host . $baseUrl . '/payfast.php',

    'm_payment_id'  => (string)$bookingId,
    'amount'        => number_format($booking['price'], 2, '.', ''),
    'item_name'     => 'Booking #' . $bookingId,
];

/* SIMPLE WORKING SIGNATURE */
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

<form action="<?php echo $url; ?>" method="post">

<?php foreach ($data as $key => $value): ?>
    <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>">
<?php endforeach; ?>

<button type="submit">Pay with PayFast</button>

</form>