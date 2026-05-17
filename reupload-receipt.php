<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id  = (int) $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

if (!$order_id) {
    header("Location: account.php");
    exit;
}

// Validate order belongs to user and is rejected
$order_q = mysqli_query($conn,
    "SELECT order_id, gcash_receipt_status, gcash_receipt, gcash_downpayment
     FROM orders
     WHERE order_id = '$order_id' AND user_id = '$user_id' LIMIT 1"
);

if (mysqli_num_rows($order_q) === 0) {
    header("Location: account.php");
    exit;
}

$order = mysqli_fetch_assoc($order_q);

if ($order['gcash_receipt_status'] !== 'rejected') {
    header("Location: receipt.php?order_id=$order_id");
    exit;
}

$success_msg = '';
$error_msg   = '';

// Handle POST upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['gcash_receipt']['tmp_name'])) {
        $error_msg = 'No file uploaded.';
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type     = mime_content_type($_FILES['gcash_receipt']['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            $error_msg = 'Invalid file type. Use PNG, JPG, or JPEG.';
        } elseif ($_FILES['gcash_receipt']['size'] > 5 * 1024 * 1024) {
            $error_msg = 'File too large. Max size is 5MB.';
        } else {
            // Delete old receipt
            if (!empty($order['gcash_receipt'])) {
                $old_path = "uploads/receipts/" . $order['gcash_receipt'];
                if (file_exists($old_path)) unlink($old_path);
            }

            // Save new receipt
            $upload_dir = "uploads/receipts/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $ext              = pathinfo($_FILES['gcash_receipt']['name'], PATHINFO_EXTENSION);
            $receipt_filename = "receipt_" . time() . "_" . $user_id . "." . $ext;
            $upload_path      = $upload_dir . $receipt_filename;

            if (!move_uploaded_file($_FILES['gcash_receipt']['tmp_name'], $upload_path)) {
                $error_msg = 'Upload failed. Please try again.';
            } else {
                $receipt_esc = mysqli_real_escape_string($conn, $receipt_filename);
                mysqli_query($conn,
                    "UPDATE orders
                     SET gcash_receipt = '$receipt_esc',
                         gcash_receipt_status = 'pending_verification',
                         gcash_rejection_reason = NULL
                     WHERE order_id = '$order_id'"
                );
                // Redirect to receipt page after success
                header("Location: receipt.php?order_id=$order_id");
                exit;
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BigBrew | Re-upload Receipt</title>
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="receipt-upload.css">
    <link rel="shortcut icon" href="assets/logo/logo-black.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }

        .topbar {
            width: 100%;
            max-width: 480px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-top: 10px;
        }

        .back-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            font-size: 15px;
        }

        .topbar h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .reupload-card {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border-radius: 20px;
            padding: 28px 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .reject-banner {
            background: #fff5f5;
            border: 1.5px solid #e57373;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: center;
        }

        .reject-banner .icon { font-size: 28px; margin-bottom: 6px; }
        .reject-banner h4 { font-size: 15px; font-weight: 700; color: #c62828; margin-bottom: 4px; }
        .reject-banner p  { font-size: 13px; color: #555; }

        .order-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9f9f9;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #555;
        }
        .order-info strong { color: #1a1a1a; font-size: 14px; }

        .upload-label {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: block;
        }

        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 14px;
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 20px;
            position: relative;
        }
        .upload-area:hover { border-color: #a0522d; background: #fdf8f5; }
        .upload-area.has-file { border-color: #81c784; background: #f0fff4; }

        .upload-area input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .upload-icon { font-size: 32px; margin-bottom: 8px; color: #aaa; }
        .upload-area.has-file .upload-icon { color: #4caf50; }

        .upload-text { font-size: 13px; color: #888; }
        .upload-text strong { color: #a0522d; }

        #previewImg {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
            border-radius: 10px;
            margin-top: 10px;
            display: none;
        }

        .error-msg {
            background: #fff5f5;
            border: 1px solid #e57373;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            color: #c62828;
            margin-bottom: 16px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #a0522d;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: #8b4513; }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; }

        .hint {
            font-size: 11px;
            color: #aaa;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="topbar">
        <a href="receipt.php?order_id=<?php echo $order_id; ?>" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h3>Re-upload GCash Receipt</h3>
    </div>

    <div class="reupload-card">

        <div class="reject-banner">
            <div class="icon">❌</div>
            <h4>Receipt Rejected</h4>
            <p>Your previous receipt was not accepted. Please upload a clear photo of your GCash payment confirmation.</p>
        </div>

        <div class="order-info">
            <span>Order</span>
            <strong>#<?php echo str_pad($order_id, 3, '0', STR_PAD_LEFT); ?></strong>
        </div>

        <?php if ($order['gcash_downpayment']): ?>
        <div class="order-info">
            <span>Required Downpayment</span>
            <strong>₱<?php echo number_format($order['gcash_downpayment'], 2); ?></strong>
        </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
        <div class="error-msg">⚠️ <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <span class="upload-label">Upload New Receipt</span>

         <div class="upload-area" id="uploadArea">
            <input type="file" name="gcash_receipt" id="fileInput" accept="image/png, image/jpeg, image/jpg">
            <div id="uploadPlaceholder">
                <div class="upload-icon">📷</div>
                <div class="upload-text">
                    Tap to choose a photo<br>
                    <strong>PNG, JPG, JPEG</strong> · Max 5MB
                </div>
            </div>
            <img id="previewImg" src="" alt="Preview" style="display:none; width:100%; max-height:200px; object-fit:contain; border-radius:10px;">
        </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                Submit Receipt
            </button>
            <p class="hint">Our staff will verify your receipt shortly.</p>
        </form>

    </div>

    <script>
   const fileInput  = document.getElementById('fileInput');
const uploadArea = document.getElementById('uploadArea');
const previewImg = document.getElementById('previewImg');
const placeholder = document.getElementById('uploadPlaceholder');

fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;

    uploadArea.classList.add('has-file');

    const reader = new FileReader();
    reader.onload = e => {
        // Hide placeholder, show preview
        placeholder.style.display = 'none';
        previewImg.src = e.target.result;
        previewImg.style.display = 'block';
    };
    reader.readAsDataURL(file);
});
    </script>

    <script src="js/orders.js"></script>
</body>
</html>