<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifePlan Contract Details</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 40px;
        }

        .section {
            margin-bottom: 35px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }

        .section:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            color: #2c3e50;
            font-size: 1.4em;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .section h2::before {
            content: '▶';
            color: #667eea;
            margin-right: 10px;
            font-size: 0.8em;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .info-label {
            font-weight: bold;
            color: #667eea;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1.1em;
            margin-top: 5px;
            color: #2c3e50;
        }

        .highlight {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }

        .highlight::before {
            content: '⚠';
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 2em;
            opacity: 0.3;
        }

        .terms-list {
            list-style: none;
            padding: 0;
        }

        .terms-list li {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 30px;
        }

        .terms-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #27ae60;
            font-weight: bold;
        }

        .terms-list li:last-child {
            border-bottom: none;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .payment-table th,
        .payment-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .payment-table th {
            background: #667eea;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9em;
        }

        .payment-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-important {
            background: #f8d7da;
            color: #721c24;
        }

        .footer {
            background: #2c3e50;
            color: white;
            padding: 20px 40px;
            text-align: center;
            font-size: 0.9em;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }
            
            .content {
                padding: 20px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>LifePlan Contract</h1>
            <p>Funeral Service Management System</p>
        </div>

        <div class="content">
</td>
                            <td><span class="status-badge status-active">Paid</span></td>
                        </tr>
                        <tr>
                            <td>Service Coverage</td>
                            <td>₱350,000.00</td>
                            <td>Upon need</td>
                            <td><span class="status-badge status-active">Guaranteed</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Death Benefit & Payment Continuation -->
            <div class="section">
                <h2>Death Benefit & Payment Obligations</h2>
                <div class="highlight">
                    <strong>Critical Information:</strong> What happens when the subscriber passes away
                </div>
                
                <h3 style="color: #2c3e50; margin: 20px 0 15px 0;">Scenario 1: Death During Payment Period</h3>
                <ul class="terms-list">
                    <li><strong>Immediate Benefit Activation:</strong> Full funeral service benefits become immediately available regardless of payment completion status</li>
                    <li><strong>Outstanding Balance:</strong> Remaining monthly payments become the responsibility of the designated co-maker</li>
                    <li><strong>Payment Schedule:</strong> Co-maker must continue monthly payments until the 60-month term is completed</li>
                    <li><strong>No Penalty Fees:</strong> No additional charges or penalties are imposed upon death of subscriber</li>
                    <li><strong>Grace Period:</strong> 30-day grace period for co-maker to arrange payment continuation</li>
                </ul>

                <h3 style="color: #2c3e50; margin: 20px 0 15px 0;">Scenario 2: Death After Full Payment</h3>
                <ul class="terms-list">
                    <li><strong>Full Benefits:</strong> Complete funeral service package available with no additional costs</li>
                    <li><strong>No Further Obligations:</strong> No payment obligations for family members or estate</li>
                    <li><strong>Transferable Rights:</strong> Benefits can be transferred to immediate family members if needed</li>
                </ul>
            </div>

            <!-- Co-Maker Responsibilities -->
            <div class="section">
                <h2>Co-Maker Responsibilities & Rights</h2>
                <div class="highlight">
                    <strong>Co-Maker Legal Obligations</strong>
                </div>
                <ul class="terms-list">
                    <li><strong>Payment Guarantee:</strong> Co-maker guarantees all payment obligations if subscriber becomes unable to pay</li>
                    <li><strong>Death Notification:</strong> Must notify the company within 72 hours of subscriber's death</li>
                    <li><strong>Documentation:</strong> Provide death certificate and legal documents for benefit processing</li>
                    <li><strong>Payment Continuation:</strong> Assume monthly payment responsibilities immediately upon subscriber's death</li>
                    <li><strong>Communication:</strong> Maintain updated contact information with the company</li>
                    <li><strong>Legal Standing:</strong> Co-maker has legal standing to make decisions regarding funeral arrangements</li>
                    <li><strong>Modification Rights:</strong> Can request payment plan modifications with company approval</li>
                    <li><strong>Benefit Access:</strong> Can claim benefits on behalf of deceased subscriber's family</li>
                </ul>
            </div>

            <!-- Service Coverage -->
            <div class="section">
                <h2>Complete Service Coverage</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Funeral Services</div>
                        <div class="info-value">Complete funeral arrangement & coordination</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Casket</div>
                        <div class="info-value">Premium wooden casket with velvet interior</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Embalming</div>
                        <div class="info-value">Professional embalming services</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Chapel Services</div>
                        <div class="info-value">3-day chapel rental with decorations</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Transportation</div>
                        <div class="info-value">Hearse and family car services</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Documentation</div>
                        <div class="info-value">All legal documents and permits</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Burial/Cremation</div>
                        <div class="info-value">Choice of burial or cremation services</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Memorial Items</div>
                        <div class="info-value">Flowers, memorial cards, and keepsakes</div>
                    </div>
                </div>
            </div>

            <!-- Default & Remedies -->
            <div class="section">
                <h2>Default & Remedies</h2>
                <div class="highlight">
                    <span class="status-badge status-important">Important</span> Consequences of missed payments
                </div>
                <ul class="terms-list">
                    <li><strong>Grace Period:</strong> 30 days grace period for all missed payments</li>
                    <li><strong>Late Fees:</strong> ₱500 penalty fee after grace period expires</li>
                    <li><strong>Contract Suspension:</strong> Benefits suspended after 60 days of non-payment</li>
                    <li><strong>Reinstatement:</strong> Contract can be reinstated within 12 months with full payment of arrears</li>
                    <li><strong>Co-Maker Liability:</strong> Co-maker becomes immediately liable for all missed payments</li>
                    <li><strong>Legal Action:</strong> Company may pursue legal remedies after 90 days of default</li>
                    <li><strong>Asset Protection:</strong> Payments made cannot be forfeited, partial benefits may apply</li>
                </ul>
            </div>

            <!-- Terms & Conditions -->
            <div class="section">
                <h2>Important Terms & Conditions</h2>
                <ul class="terms-list">
                    <li><strong>Contract Validity:</strong> This contract is valid for the lifetime of the subscriber</li>
                    <li><strong>Modification:</strong> Contract terms can only be modified with written agreement from both parties</li>
                    <li><strong>Transfer:</strong> Contract is non-transferable except to immediate family members</li>
                    <li><strong>Governing Law:</strong> This contract is governed by Philippine laws and regulations</li>
                    <li><strong>Jurisdiction:</strong> Any disputes shall be resolved in the appropriate courts of the Philippines</li>
                    <li><strong>Company Changes:</strong> Company reserves the right to update service providers while maintaining quality standards</li>
                    <li><strong>Force Majeure:</strong> Company is not liable for delays due to circumstances beyond control</li>
                    <li><strong>Privacy:</strong> All personal information is protected under data privacy laws</li>
                    <li><strong>Contact Updates:</strong> Both parties must maintain updated contact information</li>
                    <li><strong>Annual Review:</strong> Contract terms and coverage are reviewed annually</li>
                </ul>
            </div>

            <!-- Emergency Contacts -->
            <div class="section">
                <h2>Emergency Contacts & Support</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">24/7 Hotline</div>
                        <div class="info-value">1-800-LIFEPLAN (543-3752)</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Customer Service</div>
                        <div class="info-value">(02) 8XXX-XXXX</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email Support</div>
                        <div class="info-value">support@lifeplan.com.ph</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Online Portal</div>
                        <div class="info-value">www.lifeplan.com.ph/account</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p><strong>LIFEPLAN FUNERAL SERVICES</strong></p>
            <p>This contract viewing is for informational purposes only. For official contract documents, please contact our customer service.</p>
            <p>© 2025 LifePlan Funeral Services. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Add smooth scrolling and interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to sections
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => {
                section.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(10px)';
                });
                
                section.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });

            // Add click effect to info items
            const infoItems = document.querySelectorAll('.info-item');
            infoItems.forEach(item => {
                item.addEventListener('click', function() {
                    this.style.transform = 'scale(1.02)';
                    this.style.boxShadow = '0 8px 25px rgba(102, 126, 234, 0.3)';
                    
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                        this.style.boxShadow = '';
                    }, 200);
                });
            });

            // Add typing effect to important notices
            const highlights = document.querySelectorAll('.highlight');
            highlights.forEach((highlight, index) => {
                highlight.style.opacity = '0';
                highlight.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    highlight.style.transition = 'all 0.6s ease';
                    highlight.style.opacity = '1';
                    highlight.style.transform = 'translateY(0)';
                }, 200 * (index + 1));
            });

            // Add print functionality
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'p') {
                    window.print();
                    e.preventDefault();
                }
            });

            // Add status badge animation
            const statusBadges = document.querySelectorAll('.status-badge');
            statusBadges.forEach(badge => {
                badge.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.1)';
                    this.style.transition = 'transform 0.2s ease';
                });
                
                badge.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Simulate real-time updates for demonstration
            setTimeout(() => {
                const contractNumber = document.querySelector('.info-value');
                if (contractNumber && contractNumber.textContent === 'LP-2025-001234') {
                    contractNumber.style.color = '#27ae60';
                    contractNumber.style.fontWeight = 'bold';
                }
            }, 2000);
        });

        // Add contract status checker
        function checkContractStatus() {
            // Simulate contract status check
            const statusElements = document.querySelectorAll('.status-active');
            statusElements.forEach(status => {
                status.style.animation = 'pulse 2s infinite';
            });
        }

        // Add CSS for pulse animation
        const pulseStyle = document.createElement('style');
        pulseStyle.textContent = `
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }
        `;
        document.head.appendChild(pulseStyle);

        // Initialize status checker
        setTimeout(checkContractStatus, 3000);
    </script>
</body>
</html>