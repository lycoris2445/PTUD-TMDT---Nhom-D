<?php
$pageTitle = 'Liên hệ - Darling';
$pageCss   = '../css/lien-he.css'; 
include '../includes/header.php';
?>

<main>
  <div class="container-xl pt-5 pb-5">

    <h1 class="fw-bold mb-3">Contact Us</h1>

    <p class="text-secondary mb-5">
      The Darling Customer Service Center is delighted to assist you.
      <br>
      <small class="text-dark">
      Please fill out this form to submit your request.
      </small>
    </p>

    <div class="row mb-5">
      <div class="col-12">
        <div class="card p-4 p-md-5 border-0 shadow-sm">

          <h5 class="card-title mb-4 fw-bold">
            <i class="bi bi-pencil-square me-2"></i> Contact Us
          </h5>

          <form action="#" method="post">

            <h6 class="mb-3 fw-bold">Your Information</h6>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <select class="form-select">
                  <option selected>Title</option>
                  <option>Mr.</option>
                  <option>Ms./Mrs.</option>
                </select>
              </div>

              <div class="col-md-6"><input type="text" class="form-control" placeholder="First Name"></div>
              <div class="col-md-6"><input type="text" class="form-control" placeholder="Last Name"></div>
              <div class="col-md-6"><select class="form-select"><option selected>Country/Region</option></select></div>
              <div class="col-md-6"><select class="form-select"><option selected>Language</option></select></div>
              <div class="col-md-6"><input type="email" class="form-control" placeholder="Email Address"></div>
              <div class="col-md-6"><input type="tel" class="form-control" placeholder="Phone Number"></div>
            </div>

            <h6 class="mb-3 fw-bold">Your Request</h6>

            <div class="mb-4">
              <input type="text" class="form-control mb-3" placeholder="Subject">

              <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge text-bg-light border text-dark">Skin Type</span>
                <span class="badge text-bg-light border text-dark">Offers</span>
                <span class="badge text-bg-light border text-dark">Returns</span>
                <span class="badge text-bg-danger text-white">Products</span>
              </div>

              <textarea class="form-control" rows="4" placeholder="Your Message"></textarea>
              <div class="form-text text-end">0/500</div>
            </div>

            <div class="form-check mb-4">
              <input class="form-check-input" type="checkbox" id="policyCheck">
              <label class="form-check-label" for="policyCheck">
                I have read and agree to the Darling Privacy Policy
              </label>
            </div>

            <div class="text-end">
              <button type="submit" class="btn btn-primary px-4 rounded-pill">Submit</button>
            </div>

          </form>

        </div>
      </div>
    </div>

    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <div class="card text-center h-100 p-3 border-0 shadow-sm">
          <div class="card-body">
            <i class="bi bi-chat-dots-fill display-5 mb-3"></i>
            <h5 class="card-title fw-bold">CHAT ONLINE</h5>
            <p class="card-text mb-3">...</p>
            <button class="btn btn-outline-danger btn-sm rounded-pill px-3">Learn More</button>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card text-center h-100 p-3 border-0 shadow-sm">
          <div class="card-body">
            <i class="bi bi-telephone-fill display-5 mb-3"></i>
            <h5 class="card-title fw-bold">CALL US</h5>
            <p class="card-text mb-3">...</p>
            <button class="btn btn-outline-danger btn-sm rounded-pill px-3">Learn More</button>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card text-center h-100 p-3 border-0 shadow-sm">
          <div class="card-body">
            <i class="bi bi-envelope-fill display-5 mb-3"></i>
            <h5 class="card-title fw-bold">INSTANT MESSAGE</h5>
            <p class="card-text mb-3">...</p>
            <button class="btn btn-outline-danger btn-sm rounded-pill px-3">Learn More</button>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between text-center py-4 mb-5 border-top border-bottom bottom-icons">
      <div class="p-2">
        <i class="bi bi-emoji-heart-eyes-fill"></i>
        <p class="mt-2 small">No Animal Testing</p>
      </div>
      <div class="p-2">
        <i class="bi bi-chat-left-text-fill"></i>
        <p class="mt-2 small">Vegan Ingredients</p>
      </div>
      <div class="p-2">
        <i class="bi bi-shield-check"></i>
        <p class="mt-2 small">Gluten-Free</p>
      </div>
      <div class="p-2">
        <i class="bi bi-recycle"></i>
        <p class="mt-2 small">Recyclable Packaging</p>
      </div>
    </div>

  </div>
</main>

<?php include '../includes/footer.php'; ?>