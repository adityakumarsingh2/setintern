<?php
session_start();
require_once 'db_connect.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$has_profile_data = false;

if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT user_id FROM user_details WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $has_profile_data = true;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartMatch AI Engine - PM Internship Scheme</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            opacity: 0;
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-50">

<!-- Navigation Bar -->
<nav class="bg-white shadow-md py-4 sticky top-0 z-50 transition-all duration-300">
    <div class="container mx-auto flex justify-between items-center max-w-7xl">
        <div class="logo text-2xl font-bold text-teal-600">
            SmartMatch AI
        </div>
        
        <ul class="hidden md:flex space-x-8 text-gray-700 font-medium">
            <li><a href="#home" class="hover:text-teal-600 transition">Home</a></li>
            <li><a href="#how-it-works" class="hover:text-teal-600 transition">How It Works</a></li>
            <li><a href="#benefits" class="hover:text-teal-600 transition">Benefits</a></li>
            <?php if ($is_logged_in && $has_profile_data): ?>
                <li><a href="#recommended-section" class="hover:text-teal-600 transition">Internships</a></li>
            <?php endif; ?>
            <li><a href="#contact" class="hover:text-teal-600 transition">Contact</a></li>
        </ul>
        
        <div class="flex space-x-4">
            <?php if ($is_logged_in): ?>
                <a href="profile.php" class="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-200 transition">Profile</a>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">Logout</a>
            <?php else: ?>
                <a href="login.php" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition">Login</a>
                <a href="signup.php" class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-900 transition">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section id="home" class="relative bg-gradient-to-br from-teal-500 via-cyan-600 to-blue-700 text-white py-32 overflow-hidden">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-10 left-10 w-72 h-72 cdg-white rounded-full mix-blend-multiply filter blur-3xl animate-pulse"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
    </div>
    
    <div class="container mx-auto text-center relative z-10 px-4">
        <h1 class="text-6xl font-extrabold mb-6 fade-in-up leading-tight">
            SmartMatch AI Engine
        </h1>
        
        <p class="text-xl max-w-3xl mx-auto mb-10 fade-in-up leading-relaxed" style="animation-delay: 200ms;">
            A Smart Engine utilizing <strong>AI/ML</strong> to match candidates with optimal opportunities, 
            ensuring <strong>fairness, efficiency, and broad representation</strong> across all districts.
        </p>
        
        <a href="#how-it-works" class="bg-white text-teal-600 px-8 py-4 rounded-full font-bold text-lg hover:bg-gray-100 transition inline-block shadow-lg fade-in-up" style="animation-delay: 400ms;">
            Discover SmartMatch
        </a>
    </div>
</section>

<!-- Stats Section -->
<section class="bg-neutral-light py-16">
    <div class="container mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition">
                <div class="text-5xl font-bold text-teal-600 mb-2">92%</div>
                <p class="mt-3 text-gray-700 font-semibold">Match Satisfaction Rate</p>
            </div>
            
            <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition">
                <div class="text-5xl font-bold text-cyan-600 mb-2">50K+</div>
                <p class="mt-3 text-gray-700 font-semibold">Applicants Processed/Cycle</p>
            </div>
            
            <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition">
                <div class="text-5xl font-bold text-blue-600 mb-2">35%</div>
                <p class="mt-3 text-gray-700 font-semibold">Rural District Representation</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <h2 class="text-4xl font-bold text-center text-gray-800 mb-12">How SmartMatch Works</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center p-6">
                <div class="bg-teal-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-3xl">🎯</span>
                </div>
                <h3 class="text-xl font-bold mb-4 text-gray-800">Smart Matching Algorithm</h3>
                <p class="text-gray-600">
                    AI/ML algorithms analyze candidate skills, qualifications, and preferences against 
                    industry requirements for a precise match score.
                </p>
            </div>
            
            <div class="text-center p-6">
                <div class="bg-cyan-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-3xl">⚖️</span>
                </div>
                <h3 class="text-xl font-bold mb-4 text-gray-800">Equitable Selection</h3>
                <p class="text-gray-600">
                    The system prioritizes representation from rural/aspirational districts and different 
                    social categories, ensuring inclusive access.
                </p>
            </div>
            
            <div class="text-center p-6">
                <div class="bg-blue-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-3xl">📊</span>
                </div>
                <h3 class="text-xl font-bold mb-4 text-gray-800">Dynamic Capacity Planning</h3>
                <p class="text-gray-600">
                    It dynamically accounts for the past participation and current internship capacity 
                    of industries to prevent over-allocation and delays.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Key Benefits Section -->
<section id="benefits" class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <h2 class="text-4xl font-bold text-center text-gray-800 mb-12">Key Benefits</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="text-4xl mb-4">⚡</div>
                <h3 class="text-xl font-bold mb-3 text-gray-800">Accelerated Selection</h3>
                <p class="text-gray-600">
                    Reduces matching time from weeks to minutes, streamlining the entire scheme lifecycle.
                </p>
            </div>
            
            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="text-4xl mb-4">✨</div>
                <h3 class="text-xl font-bold mb-3 text-gray-800">Optimal Matches</h3>
                <p class="text-gray-600">
                    Achieves a higher rate of successful placements by prioritizing long-term fit.
                </p>
            </div>
            
            <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition">
                <div class="text-4xl mb-4">🔍</div>
                <h3 class="text-xl font-bold mb-3 text-gray-800">Fairness & Transparency</h3>
                <p class="text-gray-600">
                    Eliminates human bias in initial matching, providing auditable and objective criteria.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Problem Statement Section -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4 max-w-5xl">
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-10 rounded-2xl shadow-lg">
            <strong class="text-neutral-dark block text-xl mb-2">The Challenge: Suboptimal Selections and Delays</strong>
            
            <p>The PM Internship Scheme is vital for student industry exposure, but matching thousands of 
            applicants with the most suitable opportunities manually is a significant bottleneck. This process 
            is prone to suboptimal selections, logistical delays, and difficulties in ensuring equitable representation.</p>
            
            <p class="mt-4">This engine provides a smart, automated system designed to overcome these challenges. 
            It leverages advanced <strong>AI/ML algorithms</strong> to perform complex matching based on dynamic factors:</p>
        </div>
    </div>
</section>

<!-- User Action Section -->
<div class="bg-gradient-to-r from-teal-600 to-cyan-600 py-16">
    <section class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center">
            <?php if ($is_logged_in): ?>
                <?php if ($has_profile_data): ?>
                    <h2 class="text-3xl font-bold text-white mb-4">Your Profile is Ready!</h2>
                    <p class="mt-2 text-md text-cyan-100">You can view or edit your profile details at any time.</p>
                    <div class="mt-6 space-x-4">
                        <a href="profile.php" class="bg-white text-teal-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition inline-block">
                            View Profile
                        </a>
                        <a href="#recommended-section" class="bg-cyan-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-cyan-600 transition inline-block">
                            View Recommendations
                        </a>
                    </div>
                <?php else: ?>
                    <h2 class="text-3xl font-bold text-white mb-4">Complete Your Profile</h2>
                    <p class="mt-2 text-md text-cyan-100">Get started by filling out your application details.</p>
                    <div class="mt-6">
                        <a href="apply.php" class="bg-white text-teal-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition inline-block">
                            Get Started
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <h2 class="text-3xl font-bold text-white mb-4">Ready to Get Started?</h2>
                <p class="mt-2 text-md text-cyan-100">Industry partners and scheme administrators can access the SmartMatch dashboard now.</p>
                <div class="mt-6 space-x-4">
                    <a href="login.php" class="bg-white text-teal-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition inline-block">
                        Login
                    </a>
                    <a href="signup.php" class="bg-cyan-500 text-white px-8 py-3 rounded-lg font-semibold hover:bg-cyan-600 transition inline-block">
                        Sign Up
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if ($is_logged_in && $has_profile_data): ?>
<!-- AI RECOMMENDED INTERNSHIPS SECTION -->
<section id="recommended-section" class="py-16 bg-gradient-to-br from-teal-50 to-blue-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-800 mb-4">
                🎯 Recommended for You
            </h2>
            <p class="text-gray-600 text-lg">
                AI-powered recommendations based on your profile
            </p>
        </div>
        
        <div id="recommendations-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Loading state -->
            <div class="col-span-full text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-teal-600"></div>
                <p class="mt-4 text-gray-600">Loading your personalized recommendations...</p>
            </div>
        </div>
    </div>
</section>

<!-- ALL INTERNSHIPS SECTION -->
<section id="all-internships-section" class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-800 mb-4">
                📚 All Available Internships
            </h2>
            <p class="text-gray-600 text-lg">
                Browse all opportunities
            </p>
        </div>
        
        <div id="all-internships-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Loading state -->
            <div class="col-span-full text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-gray-600"></div>
                <p class="mt-4 text-gray-600">Loading all internships...</p>
            </div>
        </div>
    </div>
</section>

<!-- SUCCESS MODAL -->
<div id="success-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-8 max-w-md mx-4 transform transition-all">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2" id="modal-title">Success!</h3>
            <p class="text-sm text-gray-500 mb-6" id="modal-message"></p>
            <button onclick="closeModal()" class="w-full bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition">
                Close
            </button>
        </div>
    </div>
</div>

<!-- JavaScript for Internship Management -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load recommended internships
    loadRecommendations();
    
    // Load all internships
    loadAllInternships();
});

// Function to load AI recommendations
async function loadRecommendations() {
    try {
        const response = await fetch('get_recommendations.php');
        const data = await response.json();
        
        const container = document.getElementById('recommendations-container');
        
        if (data.error) {
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <p class="text-red-600">${data.error}</p>
                    <p class="text-gray-500 mt-2">${data.message || 'Please complete your profile first'}</p>
                </div>
            `;
            return;
        }
        
        if (!data.recommendations || data.recommendations.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-600">No recommendations found matching your profile.</p>
                    <p class="text-gray-500 mt-2">Try updating your profile or check all internships below.</p>
                </div>
            `;
            return;
        }
        
        // Display recommendations
        container.innerHTML = data.recommendations.map(internship => createInternshipCard(internship, true)).join('');
        
    } catch (error) {
        console.error('Error loading recommendations:', error);
        document.getElementById('recommendations-container').innerHTML = `
            <div class="col-span-full text-center py-12">
                <p class="text-red-600">Failed to load recommendations</p>
                <p class="text-gray-500 mt-2">Please try again later</p>
            </div>
        `;
    }
}

// Function to load all internships
async function loadAllInternships() {
    try {
        const response = await fetch('get_all_internships.php');
        const data = await response.json();
        
        const container = document.getElementById('all-internships-container');
        
        if (!data.success || !data.internships || data.internships.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-600">No internships available at the moment.</p>
                </div>
            `;
            return;
        }
        
        // Display all internships
        container.innerHTML = data.internships.map(internship => createInternshipCard(internship, false)).join('');
        
    } catch (error) {
        console.error('Error loading internships:', error);
        document.getElementById('all-internships-container').innerHTML = `
            <div class="col-span-full text-center py-12">
                <p class="text-red-600">Failed to load internships</p>
            </div>
        `;
    }
}

// Function to create internship card HTML
function createInternshipCard(internship, isRecommended) {
    const matchBadge = isRecommended ? `
        <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full mb-2">
            ⭐ ${Math.round(internship.total_score * 100)}% Match
        </span>
    ` : '';
    
    return `
        <div class="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow p-6 border border-gray-200">
            ${matchBadge}
            <h3 class="text-xl font-bold text-gray-800 mb-2">${internship.title}</h3>
            <p class="text-teal-600 font-semibold mb-3">${internship.company_name}</p>
            
            <p class="text-gray-600 text-sm mb-4 line-clamp-2">${internship.description || 'No description available'}</p>
            
            <div class="space-y-2 mb-4 text-sm">
                <div class="flex items-center text-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span>${internship.required_domain}</span>
                </div>
                
                <div class="flex items-center text-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>${internship.location}</span>
                </div>
                
                <div class="flex items-center text-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>₹${parseInt(internship.stipend).toLocaleString('en-IN')}/month</span>
                </div>
                
                <div class="flex items-center text-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>${internship.duration_months} months</span>
                </div>
            </div>
            
            <button 
                onclick="registerForInternship(${internship.internship_id}, '${internship.title}')" 
                class="w-full bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition font-semibold"
            >
                Register Now
            </button>
        </div>
    `;
}

// Function to register for internship
async function registerForInternship(internshipId, internshipTitle) {
    try {
        const response = await fetch('register_internship.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ internship_id: internshipId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showModal('Registration Successful!', data.message);
            
            // Refresh recommendations after 2 seconds
            setTimeout(() => {
                loadRecommendations();
            }, 2000);
        } else {
            showModal('Registration Failed', data.message);
        }
        
    } catch (error) {
        console.error('Error registering for internship:', error);
        showModal('Error', 'Failed to register. Please try again.');
    }
}

// Function to show modal
function showModal(title, message) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-message').textContent = message;
    document.getElementById('success-modal').classList.remove('hidden');
}

// Function to close modal
function closeModal() {
    document.getElementById('success-modal').classList.add('hidden');
}
</script>
<?php endif; ?>

<!-- Footer -->
<footer id="contact" class="bg-neutral-dark text-white pt-32 pb-12 relative">
    <div class="absolute top-0 left-0 right-0 h-24 bg-gradient-to-b from-transparent to-neutral-dark"></div>
    
    <div class="container mx-auto px-4 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <div>
                <h3 class="text-xl font-bold mb-4">SmartMatch AI</h3>
                <p class="text-gray-400">
                    Revolutionizing internship matching through AI-powered intelligent algorithms.
                </p>
            </div>
            
            <div>
                <h3 class="text-xl font-bold mb-4">Quick Links</h3>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="#home" class="hover:text-white transition">Home</a></li>
                    <li><a href="#how-it-works" class="hover:text-white transition">How It Works</a></li>
                    <li><a href="#benefits" class="hover:text-white transition">Benefits</a></li>
                    <li><a href="#contact" class="hover:text-white transition">Contact</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-xl font-bold mb-4">Contact Us</h3>
                <p class="text-gray-400">
                    Email: support@smartmatch.ai<br>
                    Phone: +91 1800-XXX-XXXX
                </p>
            </div>
        </div>
        
        <div class="border-t border-gray-700 pt-8 text-center text-gray-400">
            <p>&copy; 2025 SmartMatch AI Engine. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Intersection Observer for fade-in animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.fade-in-up').forEach(target => {
        observer.observe(target);
    });
</script>

</body>
</html>
