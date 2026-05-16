<?php
if (!isset($conn)) return;

// LOGIC PHASE — runs on both includes
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $bq = mysqli_query($conn, "SELECT ban_status, ban_until, no_show_count FROM users WHERE user_id = '$uid'");
    $bdata = mysqli_fetch_assoc($bq);

    $show_ban_modal = false;
    $ban_modal_msg = '';
    $ban_modal_type = '';

    if ($bdata['ban_status'] === 'temp_banned') {
        $ban_modal_type = 'temp';
        $ban_modal_msg = 'Your account is suspended until <strong>' . date('F j, Y', strtotime($bdata['ban_until'])) . '</strong> due to repeated no-shows.';
        $show_ban_modal = true;
    } elseif ($bdata['ban_status'] === 'banned') {
        $ban_modal_type = 'banned';
        $ban_modal_msg = 'Your account has been <strong>permanently suspended</strong> due to repeated no-shows.';
        $show_ban_modal = true;
    } elseif ((int)$bdata['no_show_count'] === 1) {
        $ban_modal_type = 'warning';
        $ban_modal_msg = 'You have <strong>1 no-show</strong> on record. One more will result in a <strong>7-day suspension</strong>.';
        $show_ban_modal = true;
    }
}

// STOP HERE if not render phase
if (!isset($ban_check_render)) return;
?>
<?php if (!empty($show_ban_modal)): ?>
<div id="banNotifModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6);
     z-index:99999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#1a1a1a; border-radius:18px; padding:28px 24px; width:90%; max-width:400px;
                font-family:Poppins; box-shadow:0 8px 40px rgba(0,0,0,0.5); text-align:center;">
        <div style="font-size:40px; margin-bottom:12px;">
            <?php echo $ban_modal_type === 'warning' ? '⚠️' : '🚫'; ?>
        </div>
        <h3 style="margin:0 0 10px; font-size:17px; color:#fff;">
            <?php
                if ($ban_modal_type === 'warning') echo 'No-Show Warning';
                elseif ($ban_modal_type === 'temp') echo 'Account Suspended';
                else echo 'Account Permanently Suspended';
            ?>
        </h3>
        <p style="margin:0 0 8px; font-size:13px; color:#ccc; line-height:1.6;">
            <?php echo $ban_modal_msg; ?>
        </p>
        <p style="margin:0 0 20px; font-size:13px;">
            <a href="account.php#noshow-status" style="color:#f39c12; text-decoration:underline;">
                View your no-show record →
            </a>
        </p>
        <button onclick="closeBanModal()"
            style="width:100%; padding:12px; border:none; border-radius:10px;
                   background:<?php echo $ban_modal_type === 'warning' ? '#f39c12' : '#e74c3c'; ?>;
                   color:#fff; font-family:Poppins; font-size:14px; font-weight:600; cursor:pointer;">
            I Understand
        </button>
    </div>
</div>
<script>
(function() {
    const key = 'banModalSeen_<?php echo $ban_modal_type . '_' . (int)$bdata['no_show_count']; ?>';
    if (!sessionStorage.getItem(key)) {
        document.getElementById('banNotifModal').style.display = 'flex';
        sessionStorage.setItem(key, '1');
    }
})();
function closeBanModal() {
    document.getElementById('banNotifModal').style.display = 'none';
}
document.getElementById('banNotifModal').addEventListener('click', function(e) {
    if (e.target === this) closeBanModal();
});
</script>
<?php endif; ?>