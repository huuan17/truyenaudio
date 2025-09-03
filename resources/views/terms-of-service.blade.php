<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="mb-4">Terms of Service</h1>
                <p class="text-muted">Last updated: {{ date('F d, Y') }}</p>
                
                <div class="card">
                    <div class="card-body">
                        <h2>1. Acceptance of Terms</h2>
                        <p>By accessing and using {{ config('app.name') }}, you accept and agree to be bound by the terms and provision of this agreement.</p>
                        
                        <h2>2. Description of Service</h2>
                        <p>{{ config('app.name') }} is a platform for creating and managing audio content and video generation for social media platforms including TikTok and YouTube.</p>
                        
                        <h2>3. User Accounts</h2>
                        <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times. You are responsible for safeguarding the password and for all activities that occur under your account.</p>
                        
                        <h2>4. Content</h2>
                        <p>Our service allows you to post, link, store, share and otherwise make available certain information, text, graphics, videos, or other material. You are responsible for the content that you post to the service.</p>
                        
                        <h2>5. Privacy Policy</h2>
                        <p>Your privacy is important to us. Please review our Privacy Policy, which also governs your use of the service.</p>
                        
                        <h2>6. Prohibited Uses</h2>
                        <p>You may not use our service:</p>
                        <ul>
                            <li>For any unlawful purpose or to solicit others to perform unlawful acts</li>
                            <li>To violate any international, federal, provincial, or state regulations, rules, laws, or local ordinances</li>
                            <li>To infringe upon or violate our intellectual property rights or the intellectual property rights of others</li>
                            <li>To harass, abuse, insult, harm, defame, slander, disparage, intimidate, or discriminate</li>
                            <li>To submit false or misleading information</li>
                        </ul>
                        
                        <h2>7. Termination</h2>
                        <p>We may terminate or suspend your account immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms.</p>
                        
                        <h2>8. Disclaimer</h2>
                        <p>The information on this website is provided on an "as is" basis. To the fullest extent permitted by law, this Company excludes all representations, warranties, conditions and terms.</p>
                        
                        <h2>9. Limitation of Liability</h2>
                        <p>In no event shall {{ config('app.name') }}, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential, or punitive damages.</p>
                        
                        <h2>10. Governing Law</h2>
                        <p>These Terms shall be interpreted and governed by the laws of Vietnam, without regard to its conflict of law provisions.</p>
                        
                        <h2>11. Changes to Terms</h2>
                        <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will try to provide at least 30 days notice prior to any new terms taking effect.</p>
                        
                        <h2>12. Contact Information</h2>
                        <p>If you have any questions about these Terms of Service, please contact us at:</p>
                        <ul>
                            <li>Email: support@{{ parse_url(config('app.url'), PHP_URL_HOST) }}</li>
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
