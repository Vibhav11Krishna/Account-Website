<?php
include('../db.php');
$email = $_GET['email'];

$sql = "SELECT * FROM attendance WHERE email = '$email' ORDER BY log_date DESC LIMIT 10";
$res = $conn->query($sql);

echo '<table style="width:100%; border-collapse: collapse;">
        <tr style="text-align:left; color:#64748b; font-size:12px; border-bottom:1px solid #eee;">
            <th style="padding:10px;">DATE</th>
            <th style="padding:10px;">IN</th>
            <th style="padding:10px;">OUT</th>
            <th style="padding:10px;">STATUS</th>
        </tr>';

if ($res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        $out = $row['logout_time'] ? date('h:i A', strtotime($row['logout_time'])) : '--:--';
        echo "<tr style='border-bottom:1px solid #f8fafc;'>
                <td style='padding:12px;'>".date('d M, Y', strtotime($row['log_date']))."</td>
                <td style='padding:12px; color:#059669; font-weight:bold;'>".date('h:i A', strtotime($row['login_time']))."</td>
                <td style='padding:12px; color:#dc2626;'>$out</td>
                <td style='padding:12px;'><span style='color:#059669;'>● Present</span></td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='4' style='padding:20px; text-align:center;'>No records found.</td></tr>";
}
echo '</table>';
?>