<?php
include('../db.php');
date_default_timezone_set('Asia/Kolkata');

function formatWorkingHours(?string $loginTime, ?string $logoutTime, ?string $logDate = null): string {
    if (empty($loginTime)) {
        return '—';
    }

    $baseDate = !empty($logDate) ? $logDate : date('Y-m-d');
    $startTs = strtotime($loginTime);
    if ($startTs === false && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $loginTime)) {
        $startTs = strtotime($baseDate . ' ' . $loginTime);
    }

    if (!empty($logoutTime)) {
        $endTs = strtotime($logoutTime);
        if ($endTs === false && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $logoutTime)) {
            $endTs = strtotime($baseDate . ' ' . $logoutTime);
        }
        $isLive = false;
    } else {
        if (!empty($logDate) && $logDate < date('Y-m-d')) {
            $endTs = strtotime($logDate . ' 23:59:59');
            $isLive = false;
        } else {
            $endTs = time();
            $isLive = true;
        }
    }

    if ($startTs === false || $endTs === false || $endTs < $startTs) {
        return '—';
    }

    $diffSec = $endTs - $startTs;
    $hours = floor($diffSec / 3600);
    $minutes = floor(($diffSec % 3600) / 60);

    $formatted = sprintf('%dh %02dm', $hours, $minutes);
    return $isLive ? ($formatted . ' (Live)') : $formatted;
}

$email = trim((string)($_GET['email'] ?? ''));

echo '<table style="width:100%; border-collapse: collapse;">
        <tr style="text-align:left; color:#64748b; font-size:12px; border-bottom:1px solid #eee;">
            <th style="padding:10px;">DATE</th>
            <th style="padding:10px;">IN</th>
            <th style="padding:10px;">OUT</th>
            <th style="padding:10px;">HOURS</th>
            <th style="padding:10px;">STATUS</th>
        </tr>';

if ($email !== '' && isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE email = ? ORDER BY log_date DESC LIMIT 10");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $out = !empty($row['logout_time']) ? date('h:i A', strtotime($row['logout_time'])) : '--:--';
                $hoursText = formatWorkingHours(
                    $row['login_time'] ?? null,
                    $row['logout_time'] ?? null,
                    $row['log_date'] ?? null
                );

                echo "<tr style='border-bottom:1px solid #f8fafc;'>
                        <td style='padding:12px;'>" . htmlspecialchars(date('d M, Y', strtotime($row['log_date']))) . "</td>
                        <td style='padding:12px; color:#059669; font-weight:bold;'>" . htmlspecialchars(date('h:i A', strtotime($row['login_time']))) . "</td>
                        <td style='padding:12px; color:#dc2626;'>" . htmlspecialchars($out) . "</td>
                        <td style='padding:12px; color:#0b3c74; font-weight:700;'>" . htmlspecialchars($hoursText) . "</td>
                        <td style='padding:12px;'><span style='color:#059669;'>&#9679; Present</span></td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='5' style='padding:20px; text-align:center;'>No records found.</td></tr>";
        }
        $stmt->close();
    } else {
        echo "<tr><td colspan='5' style='padding:20px; text-align:center;'>Unable to load attendance history.</td></tr>";
    }
} else {
    echo "<tr><td colspan='5' style='padding:20px; text-align:center;'>No records found.</td></tr>";
}

echo '</table>';
?>