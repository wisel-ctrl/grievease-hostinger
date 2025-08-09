<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check for correct user type
$current_directory = basename(dirname($_SERVER['PHP_SELF']));
$allowed_user_type = null;

switch ($current_directory) {
    case 'admin':
        $allowed_user_type = 1;
        break;
    case 'employee':
        $allowed_user_type = 2;
        break;
    case 'customer':
        $allowed_user_type = 3;
        break;
}

// If user is not the correct type for this page, redirect to appropriate page
if ($_SESSION['user_type'] != $allowed_user_type) {
    switch ($_SESSION['user_type']) {
        case 1:
            header("Location: ../admin/index.php");
            break;
        case 2:
            header("Location: ../employee/index.php");
            break;
        case 3:
            header("Location: ../customer/index.php");
            break;
        default:
            // Invalid user_type
            session_destroy();
            header("Location: ../Landing_Page/login.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifePlan Contract - PDF Download</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #CA8A04;
        }
        .header h1 {
            color: #2D2B30;
            margin: 0;
            font-size: 2.5em;
        }
        .header p {
            color: #CA8A04;
            margin: 10px 0 0 0;
            font-size: 1.2em;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #2D2B30;
            border-left: 4px solid #CA8A04;
            padding-left: 15px;
            margin-bottom: 20px;
        }
        .section h3 {
            color: #2D2B30;
            margin-bottom: 15px;
        }
        .benefit-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .benefit-box h4 {
            color: #2D2B30;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .benefit-list {
            list-style: none;
            padding: 0;
        }
        .benefit-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .benefit-list li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        .benefit-list li:last-child {
            border-bottom: none;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .warning-box h4 {
            color: #856404;
            margin-top: 0;
        }
        .download-btn {
            display: inline-block;
            background: #CA8A04;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 20px 0;
            transition: background-color 0.3s;
        }
        .download-btn:hover {
            background: #B08D50;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>LifePlan Contract</h1>
            <p>Funeral Service Management System</p>
        </div>

        <div class="section">
            <h2>Death Benefit & Payment Obligations</h2>
            
            <div class="benefit-box">
                <h4>Death During Payment Period</h4>
                <ul class="benefit-list">
                    <li>Full funeral service benefits become immediately available regardless of payment completion status</li>
                    <li>Remaining monthly payments become the responsibility of the designated co-maker</li>
                    <li>Co-maker must continue monthly payments until the 60-month term is completed</li>
                    <li>No additional charges or penalties are imposed upon death of subscriber</li>
                    <li>30-day grace period for co-maker to arrange payment continuation</li>
                </ul>
            </div>

            <div class="benefit-box">
                <h4>Death After Full Payment</h4>
                <ul class="benefit-list">
                    <li>Complete funeral service package available with no additional costs</li>
                    <li>No payment obligations for family members or estate</li>
                    <li>Benefits can be transferred to immediate family members if needed</li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h2>Co-Maker Responsibilities & Rights</h2>
            
            <div class="benefit-box">
                <h4>Legal Obligations</h4>
                <ul class="benefit-list">
                    <li>Assume monthly payment responsibilities immediately upon subscriber's death</li>
                    <li>Maintain updated contact information with the company</li>
                    <li>Co-maker has legal standing to make decisions regarding funeral arrangements</li>
                    <li>Can request payment plan modifications with company approval</li>
                    <li>Can claim benefits on behalf of deceased subscriber's family</li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h2>Complete Service Coverage</h2>
            
            <div class="benefit-box">
                <h4>Included Services</h4>
                <ul class="benefit-list">
                    <li>Complete funeral arrangement & coordination</li>
                    <li>Premium wooden casket with velvet interior</li>
                    <li>Professional embalming services</li>
                    <li>3-day chapel rental with decorations</li>
                    <li>Hearse and family car services</li>
                    <li>All legal documents and permits</li>
                    <li>Choice of burial or cremation services</li>
                    <li>Flowers, memorial cards, and keepsakes</li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h2>Default & Remedies</h2>
            
            <div class="warning-box">
                <h4>Important Information</h4>
                <ul class="benefit-list">
                    <li>30 days grace period for all missed payments</li>
                    <li>₱500 penalty fee after grace period expires</li>
                    <li>Benefits suspended after 60 days of non-payment</li>
                    <li>Contract can be reinstated within 12 months with full payment of arrears</li>
                    <li>Co-maker becomes immediately liable for all missed payments</li>
                    <li>Must notify the company within 72 hours of subscriber's death</li>
                    <li>Provide death certificate and legal documents for benefit processing</li>
                    <li>Co-maker guarantees all payment obligations if subscriber becomes unable to pay</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <p>This contract is subject to the terms and conditions outlined above.</p>
            <p>For inquiries, please contact our customer service department.</p>
            <p><strong>Contract Version: 2024-01</strong></p>
        </div>

        <div style="text-align: center;">
            <button class="download-btn" onclick="generatePDF()">
                📄 Download Contract as PDF
            </button>
        </div>
    </div>

    <script>
        function generatePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Set font
            doc.setFont("helvetica");
            
            // Title
            doc.setFontSize(24);
            doc.setTextColor(45, 43, 48); // #2D2B30
            doc.text("LifePlan Contract", 105, 20, { align: "center" });
            
            doc.setFontSize(14);
            doc.setTextColor(202, 138, 4); // #CA8A04
            doc.text("Funeral Service Management System", 105, 30, { align: "center" });
            
            // Reset text color
            doc.setTextColor(0, 0, 0);
            
            let yPosition = 50;
            const lineHeight = 7;
            const margin = 20;
            const pageWidth = doc.internal.pageSize.getWidth();
            const contentWidth = pageWidth - (margin * 2);
            
            // Helper function to add text with word wrapping
            function addWrappedText(text, y, fontSize = 12) {
                doc.setFontSize(fontSize);
                const lines = doc.splitTextToSize(text, contentWidth);
                doc.text(lines, margin, y);
                return y + (lines.length * lineHeight);
            }
            
            // Helper function to add section
            function addSection(title, content, y) {
                // Section title
                doc.setFontSize(16);
                doc.setFont("helvetica", "bold");
                y = addWrappedText(title, y);
                y += 5;
                
                // Section content
                doc.setFontSize(12);
                doc.setFont("helvetica", "normal");
                y = addWrappedText(content, y);
                y += 10;
                
                return y;
            }
            
            // Death Benefit Section
            yPosition = addSection("Death Benefit & Payment Obligations", 
                "• Full funeral service benefits become immediately available regardless of payment completion status\n" +
                "• Remaining monthly payments become the responsibility of the designated co-maker\n" +
                "• Co-maker must continue monthly payments until the 60-month term is completed\n" +
                "• No additional charges or penalties are imposed upon death of subscriber\n" +
                "• 30-day grace period for co-maker to arrange payment continuation", yPosition);
            
            // Check if we need a new page
            if (yPosition > 250) {
                doc.addPage();
                yPosition = 20;
            }
            
            // Co-Maker Responsibilities Section
            yPosition = addSection("Co-Maker Responsibilities & Rights",
                "• Assume monthly payment responsibilities immediately upon subscriber's death\n" +
                "• Maintain updated contact information with the company\n" +
                "• Co-maker has legal standing to make decisions regarding funeral arrangements\n" +
                "• Can request payment plan modifications with company approval\n" +
                "• Can claim benefits on behalf of deceased subscriber's family", yPosition);
            
            // Check if we need a new page
            if (yPosition > 250) {
                doc.addPage();
                yPosition = 20;
            }
            
            // Service Coverage Section
            yPosition = addSection("Complete Service Coverage",
                "• Complete funeral arrangement & coordination\n" +
                "• Premium wooden casket with velvet interior\n" +
                "• Professional embalming services\n" +
                "• 3-day chapel rental with decorations\n" +
                "• Hearse and family car services\n" +
                "• All legal documents and permits\n" +
                "• Choice of burial or cremation services\n" +
                "• Flowers, memorial cards, and keepsakes", yPosition);
            
            // Check if we need a new page
            if (yPosition > 250) {
                doc.addPage();
                yPosition = 20;
            }
            
            // Default & Remedies Section
            yPosition = addSection("Default & Remedies",
                "• 30 days grace period for all missed payments\n" +
                "• ₱500 penalty fee after grace period expires\n" +
                "• Benefits suspended after 60 days of non-payment\n" +
                "• Contract can be reinstated within 12 months with full payment of arrears\n" +
                "• Co-maker becomes immediately liable for all missed payments\n" +
                "• Must notify the company within 72 hours of subscriber's death\n" +
                "• Provide death certificate and legal documents for benefit processing\n" +
                "• Co-maker guarantees all payment obligations if subscriber becomes unable to pay", yPosition);
            
            // Footer
            if (yPosition > 250) {
                doc.addPage();
                yPosition = 20;
            }
            
            doc.setFontSize(10);
            doc.setTextColor(100, 100, 100);
            doc.text("This contract is subject to the terms and conditions outlined above.", margin, yPosition);
            yPosition += 10;
            doc.text("For inquiries, please contact our customer service department.", margin, yPosition);
            yPosition += 10;
            doc.text("Contract Version: 2024-01", margin, yPosition);
            
            // Save the PDF
            doc.save("LifePlan_Contract.pdf");
        }
    </script>
</body>
</html>
