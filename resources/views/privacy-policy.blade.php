<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="mb-4">Privacy Policy</h1>
                <p class="text-muted">Last updated: {{ date('F d, Y') }}</p>
                
                <div class="card">
                    <div class="card-body">
                        <h2>1. Information We Collect</h2>
                        <p>We collect information you provide directly to us, such as when you create an account, use our services, or contact us for support.</p>
                        
                        <h3>Personal Information</h3>
                        <ul>
                            <li>Name and email address</li>
                            <li>Account credentials</li>
                            <li>Profile information</li>
                            <li>Content you create or upload</li>
                        </ul>
                        
                        <h3>Automatically Collected Information</h3>
                        <ul>
                            <li>Log data (IP address, browser type, pages visited)</li>
                            <li>Device information</li>
                            <li>Usage data and analytics</li>
                            <li>Cookies and similar technologies</li>
                        </ul>
                        
                        <h2>2. How We Use Your Information</h2>
                        <p>We use the information we collect to:</p>
                        <ul>
                            <li>Provide, maintain, and improve our services</li>
                            <li>Process transactions and send related information</li>
                            <li>Send technical notices and support messages</li>
                            <li>Respond to your comments and questions</li>
                            <li>Monitor and analyze trends and usage</li>
                            <li>Detect, investigate and prevent fraudulent transactions</li>
                        </ul>
                        
                        <h2>3. Information Sharing</h2>
                        <p>We do not sell, trade, or otherwise transfer your personal information to third parties except as described in this policy:</p>
                        <ul>
                            <li><strong>Service Providers:</strong> We may share information with third-party service providers who perform services on our behalf</li>
                            <li><strong>Legal Requirements:</strong> We may disclose information if required by law or in response to valid legal requests</li>
                            <li><strong>Business Transfers:</strong> Information may be transferred in connection with a merger, acquisition, or sale of assets</li>
                        </ul>
                        
                        <h2>4. Third-Party Integrations</h2>
                        <p>Our service integrates with third-party platforms including:</p>
                        <ul>
                            <li><strong>TikTok:</strong> For video publishing and account management</li>
                            <li><strong>YouTube:</strong> For video uploading and channel management</li>
                            <li><strong>VBee:</strong> For text-to-speech services</li>
                        </ul>
                        <p>When you connect these accounts, you authorize us to access and use your information from these platforms in accordance with their respective privacy policies.</p>
                        
                        <h2>5. Data Security</h2>
                        <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
                        
                        <h2>6. Data Retention</h2>
                        <p>We retain your information for as long as your account is active or as needed to provide you services. We may retain certain information for legitimate business purposes or as required by law.</p>
                        
                        <h2>7. Your Rights</h2>
                        <p>You have the right to:</p>
                        <ul>
                            <li>Access and update your personal information</li>
                            <li>Delete your account and associated data</li>
                            <li>Opt out of certain communications</li>
                            <li>Request a copy of your data</li>
                        </ul>
                        
                        <h2>8. Cookies</h2>
                        <p>We use cookies and similar tracking technologies to track activity on our service and store certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.</p>
                        
                        <h2>9. Children's Privacy</h2>
                        <p>Our service is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13.</p>
                        
                        <h2>10. International Data Transfers</h2>
                        <p>Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place for such transfers.</p>
                        
                        <h2>11. Changes to This Policy</h2>
                        <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date.</p>
                        
                        <h2>12. Contact Us</h2>
                        <p>If you have any questions about this Privacy Policy, please contact us:</p>
                        <ul>
                            <li>Email: privacy@{{ parse_url(config('app.url'), PHP_URL_HOST) }}</li>
                            <li>Website: {{ config('app.url') }}</li>
                        </ul>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="{{ url('/') }}" class="btn btn-primary">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
