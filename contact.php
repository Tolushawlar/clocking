<?php
require_once 'lib/constant.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $db->real_escape_string($_POST['firstName']);
    $lastName = $db->real_escape_string($_POST['lastName']);
    $email = $db->real_escape_string($_POST['email']);
    $phone = $db->real_escape_string($_POST['phone'] ?? '');
    $company = $db->real_escape_string($_POST['company'] ?? '');
    $subject = $db->real_escape_string($_POST['subject']);
    $message = $db->real_escape_string($_POST['message']);

    $sql = "INSERT INTO contact_submissions (first_name, last_name, email, phone, company, subject, message) 
            VALUES ('$firstName', '$lastName', '$email', '$phone', '$company', '$subject', '$message')";

    if ($db->query($sql)) {
        $success_message = 'Thank you for contacting us! We will get back to you within 24 hours.';
    } else {
        $error_message = 'There was an error submitting your message. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Contact Us - TimeTrack Pro</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-white text-slate-900 font-sans antialiased">
    <!-- NAVIGATION BAR -->
    <nav class="sticky top-0 z-50 w-full bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-[#3c83f6] flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-xl">schedule</span>
                </div>
                <span class="text-xl font-bold tracking-tight text-slate-900">TimeTrack Pro</span>
            </div>
            <div class="hidden md:flex items-center gap-8">
                <a class="text-sm font-medium text-slate-700 hover:text-[#3c83f6] transition-colors" href="index.php">Home</a>
                <a class="text-sm font-medium text-slate-700 hover:text-[#3c83f6] transition-colors" href="index.php#features">Features</a>
                <a class="text-sm font-medium text-slate-700 hover:text-[#3c83f6] transition-colors" href="index.php#about">About</a>
                <a class="text-sm font-medium text-[#3c83f6] transition-colors" href="contact.php">Contact</a>
            </div>
            <div class="flex items-center gap-4">
                <a class="hidden sm:block text-sm font-medium text-slate-700 hover:text-slate-900" href="login.php">Login</a>
                <a class="flex items-center justify-center h-10 px-6 rounded-full bg-[#3c83f6] hover:bg-[#2563eb] text-white text-sm font-bold transition-all" href="contact.php">
                    Get Started
                </a>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="relative py-20 bg-gradient-to-b from-slate-50 to-white">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h1 class="text-5xl md:text-6xl font-black text-slate-900 leading-tight mb-6">
                Get in Touch
            </h1>
            <p class="text-xl text-slate-600 max-w-3xl mx-auto">
                Have questions about TimeTrack Pro? We're here to help. Reach out to our team and we'll get back to you as soon as possible.
            </p>
        </div>
    </section>

    <!-- CONTACT SECTION -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid lg:grid-cols-2 gap-16">
                <!-- Left Column - Contact Form -->
                <div>
                    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-8">
                        <h2 class="text-3xl font-bold text-slate-900 mb-2">Send us a message</h2>
                        <p class="text-slate-600 mb-8">Fill out the form below and our team will get back to you within 24 hours.</p>

                        <?php if ($success_message): ?>
                            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                                    <p class="text-green-800 font-medium"><?php echo $success_message; ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-red-600">error</span>
                                    <p class="text-red-800 font-medium"><?php echo $error_message; ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" class="space-y-6">
                            <!-- Name Fields -->
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label for="firstName" class="block text-sm font-semibold text-slate-700 mb-2">First Name</label>
                                    <input type="text" id="firstName" name="firstName" required class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-[#3c83f6] focus:ring-2 focus:ring-[#3c83f6]/20 outline-none transition-all" placeholder="John" />
                                </div>
                                <div>
                                    <label for="lastName" class="block text-sm font-semibold text-slate-700 mb-2">Last Name</label>
                                    <input type="text" id="lastName" name="lastName" required class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-[#3c83f6] focus:ring-2 focus:ring-[#3c83f6]/20 outline-none transition-all" placeholder="Doe" />
                                </div>
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                                <input type="email" id="email" name="email" required class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-[#3c83f6] focus:ring-2 focus:ring-[#3c83f6]/20 outline-none transition-all" placeholder="john.doe@example.com" />
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-semibold text-slate-700 mb-2">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-[#3c83f6] focus:ring-2 focus:ring-[#3c83f6]/20 outline-none transition-all" placeholder="+1 (555) 000-0000" />
                            </div>

                            <!-- Company -->
                            <div>
                                <label for="company" class="block text-sm font-semibold text-slate-700 mb-2">Company Name</label>
                                <input type="text" id="company" name="company" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-[#3c83f6] focus:ring-2 focus:ring-[#3c83f6]/20 outline-none transition-all" placeholder="Your Company" />
                            </div>

                            <!-- Subject -->
                            <div>
                                <label for="subject" class="block text-sm font-semibold text-slate-700 mb-2">Subject</label>
                                <select id="subject" name="subject" required class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-[#3c83f6] focus:ring-2 focus:ring-[#3c83f6]/20 outline-none transition-all">
                                    <option value="">Select a subject</option>
                                    <option value="general">General Inquiry</option>
                                    <option value="sales">Sales & Pricing</option>
                                    <option value="support">Technical Support</option>
                                    <option value="demo">Request a Demo</option>
                                    <option value="partnership">Partnership Opportunities</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- Message -->
                            <div>
                                <label for="message" class="block text-sm font-semibold text-slate-700 mb-2">Message</label>
                                <textarea id="message" name="message" rows="6" required class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-[#3c83f6] focus:ring-2 focus:ring-[#3c83f6]/20 outline-none transition-all resize-none" placeholder="Tell us how we can help you..."></textarea>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="w-full px-8 py-4 rounded-lg bg-[#3c83f6] hover:bg-[#2563eb] text-white font-bold transition-all shadow-lg flex items-center justify-center gap-2">
                                <span>Send Message</span>
                                <span class="material-symbols-outlined">send</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right Column - Contact Information -->
                <div class="space-y-8">
                    <!-- Contact Info Cards -->
                    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-8">
                        <h3 class="text-2xl font-bold text-slate-900 mb-6">Contact Information</h3>

                        <!-- Email -->
                        <div class="flex items-start gap-4 mb-6">
                            <div class="w-12 h-12 rounded-lg bg-[#3c83f6]/10 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-[#3c83f6]">email</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 mb-1">Email</h4>
                                <p class="text-slate-600">support@timetrackpro.com</p>
                                <p class="text-slate-600">sales@timetrackpro.com</p>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="flex items-start gap-4 mb-6">
                            <div class="w-12 h-12 rounded-lg bg-[#3c83f6]/10 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-[#3c83f6]">call</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 mb-1">Phone</h4>
                                <p class="text-slate-600">+1 (555) 123-4567</p>
                                <p class="text-slate-600 text-sm">Mon-Fri, 9am-6pm EST</p>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-lg bg-[#3c83f6]/10 flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-[#3c83f6]">location_on</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 mb-1">Office</h4>
                                <p class="text-slate-600">123 Business Avenue</p>
                                <p class="text-slate-600">Suite 456, New York, NY 10001</p>
                                <p class="text-slate-600">United States</p>
                            </div>
                        </div>
                    </div>

                    <!-- Business Hours -->
                    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-8">
                        <h3 class="text-2xl font-bold text-slate-900 mb-6">Business Hours</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                <span class="font-medium text-slate-900">Monday - Friday</span>
                                <span class="text-slate-600">8:00 AM - 5:00 PM</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                <span class="font-medium text-slate-900">Saturday</span>
                                <span class="text-slate-600">Closed</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="font-medium text-slate-900">Sunday</span>
                                <span class="text-slate-600">Closed</span>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media -->
                    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-8">
                        <h3 class="text-2xl font-bold text-slate-900 mb-6">Follow Us</h3>
                        <div class="flex gap-4">
                            <a href="#" class="w-12 h-12 rounded-lg bg-[#3c83f6] hover:bg-[#2563eb] flex items-center justify-center text-white transition-all">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                            </a>
                            <a href="#" class="w-12 h-12 rounded-lg bg-[#3c83f6] hover:bg-[#2563eb] flex items-center justify-center text-white transition-all">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                                </svg>
                            </a>
                            <a href="#" class="w-12 h-12 rounded-lg bg-[#3c83f6] hover:bg-[#2563eb] flex items-center justify-center text-white transition-all">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                </svg>
                            </a>
                            <a href="#" class="w-12 h-12 rounded-lg bg-[#3c83f6] hover:bg-[#2563eb] flex items-center justify-center text-white transition-all">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MAP SECTION -->
    <section class="py-16 bg-slate-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
                <div class="aspect-[21/9] bg-slate-200 relative">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d193595.15830869428!2d-74.119763973046!3d40.69766374874431!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c24fa5d33f083b%3A0xc80b8f06e177fe62!2sNew%20York%2C%20NY%2C%20USA!5e0!3m2!1sen!2s!4v1234567890123!5m2!1sen!2s"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ SECTION -->
    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-slate-900 mb-4">Frequently Asked Questions</h2>
                <p class="text-xl text-slate-600">Quick answers to common questions about getting in touch</p>
            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-xl shadow-md border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-900 mb-2">How quickly will I get a response?</h3>
                    <p class="text-slate-600">Our team typically responds to all inquiries within 24 hours during business days. For urgent matters, please call our support line.</p>
                </div>

                <div class="bg-white rounded-xl shadow-md border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-900 mb-2">Can I schedule a product demo?</h3>
                    <p class="text-slate-600">Absolutely! Select "Request a Demo" in the subject field of the contact form, and our sales team will reach out to schedule a personalized demonstration.</p>
                </div>

                <div class="bg-white rounded-xl shadow-md border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-900 mb-2">Do you offer technical support?</h3>
                    <p class="text-slate-600">Yes, we provide comprehensive technical support to all our customers. Select "Technical Support" in the contact form or call our support line during business hours.</p>
                </div>

                <div class="bg-white rounded-xl shadow-md border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-900 mb-2">Where is your office located?</h3>
                    <p class="text-slate-600">Our main office is located at 123 Business Avenue, Suite 456, New York, NY 10001. We welcome scheduled visits during business hours.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA SECTION -->
    <section class="py-20 bg-gradient-to-r from-[#3c83f6] to-[#2563eb]">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <h2 class="text-4xl md:text-5xl font-black text-white mb-6">Ready to Get Started?</h2>
            <p class="text-xl text-white/90 mb-8">Join thousands of businesses already using TimeTrack Pro to streamline their workforce management.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a class="px-8 py-4 rounded-lg bg-white hover:bg-slate-50 text-[#3c83f6] font-bold transition-all shadow-lg" href="register.php">
                    Start Free Trial
                </a>
                <a class="px-8 py-4 rounded-lg border-2 border-white hover:bg-white/10 text-white font-bold transition-all" href="index.php">
                    Learn More
                </a>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-slate-900 text-slate-300 py-16">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                <!-- Column 1: Branding & Contact -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-[#3c83f6] flex items-center justify-center text-white">
                            <span class="material-symbols-outlined text-xl">schedule</span>
                        </div>
                        <span class="text-xl font-bold text-white">TimeTrack Pro</span>
                    </div>
                    <p class="text-sm mb-4">The modern way businesses track time, manage teams, and deliver projects.</p>
                    <div class="space-y-2 text-sm">
                        <p>support@timetrackpro.com</p>
                        <p>+1 (555) 123-4567</p>
                    </div>
                </div>

                <!-- Column 2: Product -->
                <div>
                    <h4 class="text-white font-bold mb-4">Product</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="index.php#features" class="hover:text-white transition-colors">Features</a></li>
                        <li><a href="index.php#pricing" class="hover:text-white transition-colors">Pricing</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Integrations</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Case Studies</a></li>
                    </ul>
                </div>

                <!-- Column 3: Support -->
                <div>
                    <h4 class="text-white font-bold mb-4">Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="contact.php" class="hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Documentation</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">API Reference</a></li>
                    </ul>
                </div>

                <!-- Column 4: Company -->
                <div>
                    <h4 class="text-white font-bold mb-4">Company</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="index.php#about" class="hover:text-white transition-colors">About Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Partners</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row justify-between items-center gap-4 text-sm">
                <p class="text-slate-500">© 2026 TimeTrack Pro. All rights reserved.</p>
                <div class="flex gap-6">
                    <a href="#" class="text-slate-500 hover:text-white transition-colors">Privacy Policy</a>
                    <a href="#" class="text-slate-500 hover:text-white transition-colors">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>