<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookies Policy | Karunesh Kumar & Associates</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #0b3c74;
            --accent: #ff8c00;
            --text-main: #1e293b;
            --text-light: #64748b;
            --bg-soft: #f8fafc;
            --border: #e2e8f0;
        }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-soft);
            color: var(--text-main);
            line-height: 1.7;
        }

        .container {
            max-width: 1000px;
            margin: 60px auto;
            padding: 0 20px;
        }

        /* Hero Section */
        .policy-header {
            background: white;
            padding: 60px;
            border-radius: 24px 24px 0 0;
            border: 1px solid var(--border);
            border-bottom: none;
            text-align: left;
        }

        .policy-header h1 {
            font-size: 42px;
            font-weight: 800;
            color: var(--primary);
            margin: 0 0 10px 0;
            letter-spacing: -1px;
        }

        .last-updated {
            color: var(--accent);
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 8px;
        }

        /* Content Section */
        .policy-content {
            background: white;
            padding: 0 60px 60px 60px;
            border-radius: 0 0 24px 24px;
            border: 1px solid var(--border);
            border-top: none;
        }

        .policy-content h2 {
            font-size: 24px;
            color: var(--primary);
            margin-top: 40px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 10px;
        }

        .policy-content p {
            margin-bottom: 20px;
            color: var(--text-light);
        }

        /* Professional Table */
        .cookie-table-wrapper {
            overflow-x: auto;
            margin: 30px 0;
        }

        .cookie-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .cookie-table th {
            background: #f8fafc;
            padding: 15px;
            border-bottom: 2px solid var(--border);
            color: var(--primary);
            font-weight: 700;
        }

        .cookie-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .cookie-name {
            font-family: monospace;
            color: var(--accent);
            background: #fff7ed;
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* Custom Box */
        .notice-box {
            background: #f0f7ff;
            border-left: 4px solid var(--primary);
            padding: 25px;
            border-radius: 0 12px 12px 0;
            margin: 30px 0;
        }

        /* Links */
        .nav-back {
            margin-bottom: 20px;
            display: inline-block;
            text-decoration: none;
            color: var(--primary);
            font-weight: 700;
            transition: 0.3s;
        }
        .nav-back:hover { color: var(--accent); }

        .browser-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        .browser-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .policy-header, .policy-content { padding: 30px; }
            .policy-header h1 { font-size: 30px; }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="Register.php" class="nav-back"><i class="fas fa-arrow-left"></i> Return to Portal</a>
    
    <header class="policy-header">
        <span class="last-updated">Last Updated: March 04, 2026</span>
        <h1>Cookies Policy</h1>
        <p>How Karunesh Kumar & Associates manages digital identifiers.</p>
    </header>

    <main class="policy-content">
        <section>
            <h2>1. Introduction</h2>
            <p>This Cookies Policy explains how Karunesh Kumar & Associates ("Firm", "we", "us", and "our") uses cookies and similar technologies to recognize you when you visit our portal. It explains what these technologies are and why we use them, as well as your rights to control our use of them.</p>
        </section>

        <section>
            <h2>2. What are Cookies?</h2>
            <p>Cookies are small data files that are placed on your computer or mobile device when you visit a website. Cookies are widely used by website owners in order to make their websites work, or to work more efficiently, as well as to provide reporting information.</p>
        </section>

        <section>
            <h2>3. Cookies We Use</h2>
            <p>We use first-party cookies for several reasons. Some cookies are required for technical reasons in order for our Portal to operate, and we refer to these as "essential" or "strictly necessary" cookies.</p>
            
            <div class="cookie-table-wrapper">
                <table class="cookie-table">
                    <thead>
                        <tr>
                            <th>Classification</th>
                            <th>ID/Name</th>
                            <th>Purpose</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Strictly Necessary</strong></td>
                            <td><span class="cookie-name">PHPSESSID</span></td>
                            <td>Maintains your encrypted login session to prevent unauthorized access to financial data.</td>
                            <td>Session</td>
                        </tr>
                        <tr>
                            <td><strong>Security</strong></td>
                            <td><span class="cookie-name">XSRF-TOKEN</span></td>
                            <td>Used to prevent Cross-Site Request Forgery (CSRF) attacks on your account.</td>
                            <td>2 Hours</td>
                        </tr>
                        <tr>
                            <td><strong>Functionality</strong></td>
                            <td><span class="cookie-name">kka_portal_mode</span></td>
                            <td>Remembers your preference between "Client Portal" and "Office Portal" views.</td>
                            <td>30 Days</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2>4. Third-Party Analytics</h2>
            <p>Unlike public websites, this Secure Portal <strong>does not</strong> use third-party behavioral tracking or advertising cookies. We do not share your browsing patterns with companies like Google, Meta, or any advertising networks.</p>
        </section>

        <div class="notice-box">
            <strong>Note on Performance:</strong> Because our cookies are primarily "Strictly Necessary," disabling them via your browser will result in the inability to log in to your secure account or access financial reports.
        </div>

        <section>
            <h2>5. How Can I Control Cookies?</h2>
            <p>You have the right to decide whether to accept or reject cookies. You can set or amend your web browser controls to accept or refuse cookies. If you choose to reject cookies, you may still use our website though your access to some functionality and areas of our website may be restricted.</p>
            <p>To learn more about how to manage cookies on your browser, please visit the official help pages for:</p>
            <ul class="browser-links" style="color: var(--text-light); font-size: 14px;">
                <li><a href="https://support.google.com/chrome/answer/95647" target="_blank">Google Chrome</a></li>
                <li><a href="https://support.mozilla.org/en-US/kb/enhanced-tracking-protection-firefox-desktop" target="_blank">Mozilla Firefox</a></li>
                <li><a href="https://support.apple.com/guide/safari/manage-cookies-sfri11471/mac" target="_blank">Apple Safari</a></li>
                <li><a href="https://support.microsoft.com/en-us/windows/microsoft-edge-browsing-data-and-privacy-bb8174ba-9d73-dcf2-9b4a-c582b4e640dd" target="_blank">Microsoft Edge</a></li>
            </ul>
        </section>

        <section>
            <h2>6. Updates to this Policy</h2>
            <p>We may update this Cookies Policy from time to time in order to reflect, for example, changes to the cookies we use or for other operational, legal or regulatory reasons. Please therefore re-visit this Cookies Policy regularly to stay informed about our use of cookies and related technologies.</p>
        </section>

        <section>
            <h2>7. Contact Information</h2>
            <p>If you have any questions about our use of cookies or other technologies, please email us at:</p>
            <p><strong>karunesh.cma@gmail.com</strong></p>
        </section>
    </main>
</div>

</body>
</html>