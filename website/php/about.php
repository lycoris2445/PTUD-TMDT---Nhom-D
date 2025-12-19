<?php
$pageTitle = 'About Us - Darling Cosmetics';
$pageCss   = '../css/gioi-thieu.css'; 
include 'header.php';
?>
<style>
    /* Ép giao diện hiển thị đúng trên máy tính */
    @media (min-width: 768px) {
        .about-frame {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 40px !important;
        }
        .about-text {
            flex: 1 !important;
            width: 45% !important; /* Chia đôi màn hình */
            max-width: 45% !important;
        }
        .about-image {
            flex: 1 !important;
            width: 45% !important; /* Chia đôi màn hình */
            max-width: 45% !important;
            height: 400px; /* Chiều cao cố định */
        }
        .about-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 20px;
        }
    }
    
    /* Giao diện trên điện thoại (Xếp chồng dọc) */
    @media (max-width: 767px) {
        .about-frame {
            flex-direction: column !important;
        }
        .about-text, .about-image {
            width: 100% !important;
            max-width: 100% !important;
            margin-bottom: 20px;
        }
    }
</style>
<main>
    <div class="container">
        
        <div class="about-header">
            <h2>Our Story & Vision</h2>
            <p>Welcome to Darling. We are here to redefine beauty standards with clean ingredients, sustainable practices, and a whole lot of love.</p>
        </div>

        <section class="about-frame">
            <div class="about-text">
                <h3>The Darling Story</h3>
                <p>
                    Founded in 2025, Darling was born from a simple yet powerful belief: <strong>Beauty should be clean, conscious, and confident.</strong>
                </p>
                <p>
                    We started in a small studio with a passion for natural ingredients and a rejection of harmful chemicals. 
                    Frustrated by the lack of transparency in the beauty industry, we set out to create a brand that honors your skin and the planet equally.
                </p>
            </div>
            <div class="about-image">
                <img src="https://i.pinimg.com/736x/9f/88/01/9f880100ad711d2173157e9c9452ec19.jpg" alt="Our Story">
            </div>
        </section>
        
        <section class="about-frame reverse">
            <div class="about-text">
                <h3>Mission & Vision</h3>
                <p>
                    <strong>Our Mission:</strong> To empower individuals to embrace their natural beauty by providing high-performance products that are safe, ethical, and effective.
                </p>
                <p>
                    <strong>Our Vision:</strong> To become a global leader in the clean beauty revolution, proving that you don't have to compromise on quality to be kind to the earth.
                </p>
            </div>
            <div class="about-image">
                <img src="https://i.pinimg.com/1200x/38/9f/d5/389fd5f560ce4a371cdacfb1037488e7.jpg" alt="Mission">
            </div>
        </section>

        <section class="values-section">
            <h3>Core Values</h3>
            <p class="subtitle">The pillars that define who we are.</p>
            
            <div class="values-grid">
                <div class="value-card">
                    <img src="https://cdn-icons-png.flaticon.com/512/2913/2913465.png" width="50" alt="Quality">
                    <h4>Quality First</h4>
                    <p>We never cut corners. Every formula is rigorously tested for performance.</p>
                </div>
                <div class="value-card">
                    <img src="https://cdn-icons-png.flaticon.com/512/2913/2913564.png" width="50" alt="Safety">
                    <h4>100% Safe</h4>
                    <p>Clean ingredients only. No parabens, no sulfates, no hidden toxins.</p>
                </div>
                <div class="value-card">
                    <img src="https://cdn-icons-png.flaticon.com/512/2913/2913604.png" width="50" alt="Customer">
                    <h4>You First</h4>
                    <p>Your skin's health and your satisfaction are our top priorities.</p>
                </div>
            </div>
        </section>
        
        <section class="about-frame">
            <div class="about-text">
                <h3>Meet The Founder</h3>
                <p>
                    <em>"I believe that beauty is a feeling, not just a look. I wanted to create a brand that makes you feel good about yourself and the choices you make."</em>
                </p>
                <p>
                    With over 10 years of experience in dermatology and a passion for sustainable living, <strong>Sophia Darling</strong> launched this brand to bridge the gap between scientific skincare and natural beauty.
                </p>
            </div>
            <div class="about-image">
                <img src="https://i.pinimg.com/1200x/98/9d/6e/989d6efde9ff7ac168ef00942936737a.jpg" alt="Founder">
            </div>
        </section>
        
        <section class="commitment-section">
            <h2 class="section-title" style="color:#3b001f; margin-bottom: 20px;">The Darling Commitment</h2>
            
            <div class="content-box">
                <p>When you choose Darling, you are choosing:</p>
                <ul>
                    <li><strong>Cruelty-Free:</strong> We never test on animals. Ever.</li>
                    <li><strong>Transparency:</strong> We list every single ingredient on our labels.</li>
                    <li><strong>Sustainability:</strong> Our packaging is made from recycled materials.</li>
                </ul>
            </div>
        </section>
    </div>
</main>
<?php include 'footer.php'; ?>