<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Terms of Service</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'playfair': ['"Playfair Display"', 'serif'],
                        'alexbrush': ['"Alex Brush"', 'cursive'],
                        'inter': ['Inter', 'sans-serif'],
                        'cinzel': ['Cinzel', 'serif'],
                        'hedvig': ['Hedvig Letters Serif', 'serif']
                    },
                    colors: {
                        'yellow': {
                            600: '#CA8A04',
                        },
                        'navy': '#2D2B30',
                        'cream': '#F9F6F0',
                        'dark': '#1E1E1E',
                        'gold': '#C9A773',
                        'darkgold': '#B08D50',
                        'primary': '#2D2B30',
                        'primary-foreground': '#FFFFFF',
                        'secondary': '#F1F5F9',
                        'secondary-foreground': '#1E1E1E',
                        'border': '#E4E9F0',
                        'input-border': '#D3D8E1',
                        'error': '#E53E3E',
                        'success': '#38A169',
                    },
                    boxShadow: {
                        'input': '0 1px 2px rgba(0, 0, 0, 0.05)',
                        'card': '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-cream text-dark font-inter">
    <!-- Full-Page Hero Section -->
    <div class="relative w-full h-[50vh] overflow-hidden">
        <div class="absolute inset-0 bg-center bg-cover bg-no-repeat" 
             style="background-image: url('Landing_Page/Landing_images/black-bg-image.jpg');">
            <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/40 to-black/80"></div>
        </div>
        
        <div class="relative h-full flex items-center justify-center px-6 md:px-12 z-10">
            <div class="text-center" data-aos="fade-up" data-aos-delay="300" data-aos-duration="1000">
                <h1 class="font-hedvig text-5xl sm:text-6xl lg:text-7xl text-white text-shadow-lg mb-4">
                    Terms of Service
                </h1>
                <p class="text-white/80 max-w-2xl mx-auto text-lg">Last Updated: March 2024</p>
            </div>
        </div>
    </div>

    <!-- Terms of Service Content -->
    <div class="container mx-auto px-6 py-16 max-w-4xl">
        <div class="bg-white rounded-2xl shadow-lg p-8 md:p-12">
            <div class="prose prose-lg max-w-none">
                <section class="mb-12">
                    <h2 class="text-2xl font-hedvig text-navy mb-4">1. Acceptance of Terms</h2>
                    <p>By accessing and using the GrievEase website and services, you agree to be bound by these Terms of Service. If you do not agree with these terms, please do not use our services.</p>
                </section>

                <section class="mb-12">
                    <h2 class="text-2xl font-hedvig text-navy mb-4">2. Services</h2>
                    <p>GrievEase provides funeral and memorial services with the utmost compassion and respect. We reserve the right to modify, suspend, or discontinue any aspect of our services at any time.</p>
                </section>

                <section class="mb-12">
                    <h2 class="text-2xl font-hedvig text-navy mb-4">3. User Responsibilities</h2>
                    <ul class="list-disc list-inside space-y-2">
                        <li>Provide accurate and complete information during service arrangements</li>
                        <li>Respect the guidelines and policies of our funeral home</li>
                        <li>Treat our staff with dignity and respect</li>
                    </ul>
                </section>

                <section class="mb-12">
                    <h2 class="text-2xl font-hedvig text-navy mb-4">4. Privacy</h2>
                    <p>We are committed to protecting your privacy. Please review our Privacy Policy, which explains how we collect, use, and protect your personal information.</p>
                </section>

                <section class="mb-12">
                    <h2 class="text-2xl font-hedvig text-navy mb-4">5. Payment and Fees</h2>
                    <p>All fees for services are due at the time of service unless otherwise arranged. We accept various payment methods and can discuss payment plans during consultation.</p>
                </section>

                <section class="mb-12">
                    <h2 class="text-2xl font-hedvig text-navy mb-4">6. Limitation of Liability</h2>
                    <p>GrievEase strives to provide compassionate and professional services. However, we are not liable for any indirect, incidental, or consequential damages arising from our services.</p>
                </section>
            </div>
        </div>
    </div>


    <?php include 'footer.php' ?>
</body>
</html>