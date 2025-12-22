<?php
$pageTitle = 'Home - Darling Cosmetics';
$pageCss   = 'trang-chu.css'; 

// Kết nối database (giữ nguyên để dùng sau này)
$pdo = require __DIR__ . '/../../config/db_connect.php';

include '../includes/header.php';
?>

<main>

    <section class="hero-section">
        <div class="container">
            <h1>REVEAL YOUR TRUE RADIANCE</h1>
            <p>Clean beauty products designed to make you shine from within.</p>
            <a href="store.php" class="btn btn-dark mt-3">Shop Now</a>
        </div>
    </section>

    <section class="featured-products container">
        <h2 class="section-title">Featured Products</h2>
        <div class="product-grid">
            <div class="product-card">
                <div class="product-image-placeholder">Product Image</div>
                <h4>Velvet Matte Lipstick</h4>
                <p>$18.00</p>
            </div>
            <div class="product-card">
                <div class="product-image-placeholder">Product Image</div>
                <h4>Hydrating Serum</h4>
                <p>$24.50</p>
            </div>
            <div class="product-card">
                <div class="product-image-placeholder">Product Image</div>
                <h4>Rose Water Toner</h4>
                <p>$15.00</p>
            </div>
            <div class="product-card">
                <div class="product-image-placeholder">Product Image</div>
                <h4>Night Repair Cream</h4>
                <p>$32.00</p>
            </div>
        </div>
    </section>
    
    <section class="categories-section container">
        <h2 class="section-title">Shop by Category</h2>
        <div class="category-grid">
            
            <div class="category-card">
                <div class="category-image">
                    <img src="https://c8.alamy.com/comp/2BJC15F/set-of-body-care-products-isolated-on-white-2BJC15F.jpg" alt="Skincare">
                </div>
                <h3>Skincare</h3>
            </div>

            <div class="category-card">
                <div class="category-image">
                    <img src="https://images.squarespace-cdn.com/content/v1/666fcdf1b40ced5c1847a5bb/66b9cd20-db78-4ebf-a0f8-74d7978774bd/YB+Full+Line+1.jpg?format=1000w" alt="Makeup">
                </div>
                <h3>Makeup</h3>
            </div>

            <div class="category-card">
                <div class="category-image">
                    <img src="https://t4.ftcdn.net/jpg/03/23/31/57/360_F_323315785_tfrtUr3pUWOfUcqHPQ4MMcbZ3eoJb96L.jpg" alt="Body Care">
                </div>
                <h3>Body Care</h3>
            </div>

        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <h2>Glow Up With Darling</h2>
            <p>Get 30% off your first order when you join our community.</p>
            <a href="register.php" class="btn btn-outline-dark mt-2">Join Now</a>
        </div>
    </section>
    
    <section class="testimonials-section container">
        <h2 class="section-title">Customer Love</h2>
        <div class="testimonials-grid">
            
            <div class="testimonial-card">
                <div class="testimonial-avatar">
                    <img src="https://i.pinimg.com/1200x/df/ab/f5/dfabf5cc413d52110ce3d5a7150c9698.jpg" alt="Sarah">
                </div>
                <div class="stars">★★★★★</div>
                <p>"The best lipstick I've ever used! It stays on all day and doesn't dry out my lips. Highly recommend!"</p>
                <h5>- Sarah J.</h5>
            </div>

            <div class="testimonial-card">
                <div class="testimonial-avatar">
                    <img src="https://i.pinimg.com/736x/20/ac/3c/20ac3c3a2d0e1cd6cb2e9761d0dceeb8.jpg" alt="Emily">
                </div>
                <div class="stars">★★★★★</div>
                <p>"Finally, a skincare brand that is actually 100% cruelty-free. My sensitive skin loves the serum."</p>
                <h5>- Emily R.</h5>
            </div>

            <div class="testimonial-card">
                <div class="testimonial-avatar">
                    <img src="https://i.pinimg.com/736x/57/50/a7/5750a757b879ce325cc17c242fb7f7b9.jpg" alt="Jessica">
                </div>
                <div class="stars">★★★★☆</div>
                <p>"Fast shipping and the packaging is so eco-friendly. I'm in love with the Rose Water Toner."</p>
                <h5>- Jessica T.</h5>
            </div>

        </div>
    </section>

    <section class="content-section container">
        <h2 class="section-title">The Darling Story</h2>
        <div class="content-box">
            <p>
                Founded in 2025, Darling is committed to conscious beauty. We believe that what you put on your skin matters. 
                That's why all our products are formulated with ethically sourced ingredients, are 100% cruelty-free, and come in sustainable packaging. 
                Beauty without compromise.
            </p>
        </div>
    </section>
    
    <section class="newsletter-section container">
        <h2 class="section-title">Stay in the Loop</h2>
        <div class="newsletter-box">
            <p>Subscribe to receive updates, access to exclusive deals, and more.</p>
            <form action="#" method="POST" class="newsletter-form">
                <input type="email" class="newsletter-form-input" placeholder="Enter your email address" required>
                <button type="submit" class="newsletter-form-button">Subscribe</button>
            </form>
        </div>
    </section>

</main>
<?php include '../includes/footer.php'; ?>