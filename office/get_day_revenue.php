<?php
include('../db.php');

// Security: Ensure date is set and basic sanitization
$date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');
$display_date = date('d M, Y', strtotime($date));

$sql = "SELECT r.receipt_no, r.amount_paid, u.name 
        FROM receipts r 
        JOIN users u ON r.client_id = u.identifier 
        WHERE DATE(r.created_at) = '$date'";
$res = $conn->query($sql);

// Header of the Box
echo "<div style='margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;'>
        <h3 style='margin: 0; color: #0b3c74; font-size: 18px;'>Revenue Breakdown</h3>
        <span style='font-size: 13px; color: #64748b;'><i class='fas fa-calendar-alt'></i> $display_date</span>
      </div>";

if($res->num_rows > 0) {
    echo "<div style='max-height: 300px; overflow-y: auto;'>";
    echo "<table style='width:100%; border-collapse: collapse; font-family: sans-serif;'>";
    
    $total_day = 0;
    while($row = $res->fetch_assoc()) {
        $total_day += $row['amount_paid'];
        echo "<tr style='border-bottom: 1px solid #f8fafc;'>
                <td style='padding: 12px 0;'>
                    <span style='display:block; font-weight: 700; color: #334155; font-size: 14px;'>{$row['name']}</span>
                    <small style='color: #ff8c00; font-weight: 600;'>#{$row['receipt_no']}</small>
                </td>
                <td style='text-align: right; padding: 12px 0; font-weight: 800; color: #0b3c74; font-size: 14px;'>
                    ₹" . number_format($row['amount_paid'], 2) . "
                </td>
              </tr>";
    }
    echo "</table>";
    echo "</div>";

    // Summary Footer
    echo "<div style='margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;'>
            <span style='font-weight: 700; color: #64748b; font-size: 13px; text-transform: uppercase;'>Day's Total</span>
            <span style='font-size: 20px; font-weight: 900; color: #059669;'>₹" . number_format($total_day, 2) . "</span>
          </div>";
} else {
    echo "<div style='text-align:center; padding: 40px 0;'>
            <i class='fas fa-folder-open' style='font-size: 40px; color: #e2e8f0; margin-bottom: 10px;'></i>
            <p style='color: #94a3b8; margin: 0;'>No transactions recorded for this date.</p>
          </div>";
}
?>