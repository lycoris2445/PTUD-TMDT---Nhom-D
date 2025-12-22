// Stripe Checkout Integration
// Handles Stripe card payment flow for order.php

(function() {
  'use strict';

  // Initialize Stripe with publishable key
  let stripe = null;
  let elements = null;
  let cardElement = null;
  
  // DOM elements
  const form = document.getElementById('checkout-form');
  const paymentRadios = document.querySelectorAll('input[name="payment"]');
  const stripeWrapper = document.getElementById('stripe-card-wrapper');
  const cardErrors = document.getElementById('card-errors');
  const submitBtn = document.getElementById('submit-btn');
  const btnText = document.getElementById('btn-text');
  const btnSpinner = document.getElementById('btn-spinner');
  const orderNote = document.getElementById('order-note');

  // Check if we're on checkout page
  if (!form || !stripeWrapper) {
    console.log('[Stripe] Not on checkout page, skipping initialization');
    return;
  }

  // Initialize Stripe
  async function initStripe() {
    try {
      // Get publishable key from backend
      const response = await fetch('../../config/stripe.php?get_pk=1');
      const data = await response.json();
      
      if (!data.success || !data.publishable_key) {
        console.error('[Stripe] Failed to load publishable key');
        return;
      }

      stripe = Stripe(data.publishable_key);
      elements = stripe.elements();
      
      // Create card element with styling
      cardElement = elements.create('card', {
        style: {
          base: {
            fontSize: '16px',
            color: '#32325d',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            '::placeholder': {
              color: '#aab7c4'
            }
          },
          invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
          }
        }
      });

      cardElement.mount('#card-element');

      // Handle real-time validation errors
      cardElement.on('change', function(event) {
        if (event.error) {
          cardErrors.textContent = event.error.message;
        } else {
          cardErrors.textContent = '';
        }
      });

      console.log('[Stripe] Initialized successfully');
    } catch (error) {
      console.error('[Stripe] Initialization error:', error);
      showMessage('Không thể khởi tạo Stripe. Vui lòng thử lại.', 'danger');
    }
  }

  // Toggle Stripe card input visibility
  function toggleStripeInput() {
    const selectedPayment = document.querySelector('input[name="payment"]:checked').value;
    if (selectedPayment === 'STRIPE') {
      stripeWrapper.style.display = 'block';
      if (!stripe) {
        initStripe();
      }
    } else {
      stripeWrapper.style.display = 'none';
      cardErrors.textContent = '';
    }
  }

  // Bind payment method change events
  paymentRadios.forEach(radio => {
    radio.addEventListener('change', toggleStripeInput);
  });

  // Convert VND to USD for Stripe (Stripe requires USD/EUR/etc)
  function vndToUsd(vnd) {
    const exchangeRate = 25000; // Approximate rate, adjust as needed
    return Math.max(0.50, Math.round((vnd / exchangeRate) * 100) / 100);
  }

  // Show message to user
  function showMessage(message, type = 'info') {
    orderNote.textContent = message;
    orderNote.className = `mt-2 small text-${type === 'danger' ? 'danger' : type === 'success' ? 'success' : 'info'}`;
  }

  // Set loading state
  function setLoading(isLoading) {
    submitBtn.disabled = isLoading;
    btnText.textContent = isLoading ? 'Đang xử lý...' : 'Đặt hàng';
    btnSpinner.style.display = isLoading ? 'inline-block' : 'none';
  }

  // Process COD order
  async function processCODOrder(formData) {
    try {
      if (!window.Cart) {
        throw new Error('Cart module not loaded');
      }
      
      const cart = window.Cart.read();
      console.log('[COD] Cart contents:', cart);
      
      if (!cart || !cart.length) {
        throw new Error('Giỏ hàng trống');
      }
      
      const totals = window.Cart.getTotals(cart);
      
      console.log('[COD] Creating order...', {
        cart: cart.length + ' items',
        total: totals.total
      });
      
      // Call unified payment endpoint with COD strategy
      const response = await fetch('../../api/payments/process-payment.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          payment_method: 'COD',
          cart: cart.map(item => ({
            id: item.id,
            name: item.name,
            price: item.price,
            qty: item.qty
          })),
          shipping_info: {
            fullname: formData.get('fullname'),
            phone: formData.get('phone'),
            email: formData.get('email') || '',
            address: formData.get('address'),
            city: formData.get('city') || '',
            district: formData.get('district') || ''
          },
          note: formData.get('note') || '',
          total_amount: totals.total,
          shipping_fee: totals.shipping,
          shipping_carrier: formData.get('shipping_carrier') || 'GHN'
        })
      });
      
      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.message || 'Failed to create order');
      }
      
      console.log('[COD] Order created:', data.data.order_id);
      
      showMessage(`Đặt hàng thành công! Mã đơn: ${data.data.tracking_number}`, 'success');
      
      // Clear cart
      window.Cart.clear();
      
      // Redirect after delay
      setTimeout(() => {
        window.location.href = 'order-success.php?order_id=' + data.data.order_id + '&method=COD&tracking=' + data.data.tracking_number;
      }, 1500);
      
      return true;
    } catch (error) {
      console.error('[COD] Error:', error);
      showMessage('Đặt hàng thất bại: ' + error.message, 'danger');
      return false;
    }
  }

  // Process Stripe payment
  async function processStripePayment(formData) {
    try {
      if (!window.Cart) {
        throw new Error('Cart module not loaded');
      }
      
      const cart = window.Cart.read();
      console.log('[Stripe] Cart contents:', cart);
      
      if (!cart || !cart.length) {
        showMessage('Giỏ hàng trống!', 'danger');
        return false;
      }

      const totals = window.Cart.getTotals(cart);
      
      // Totals are already in USD from cart.js
      const amountUSD = totals.total;
      
      console.log('[Stripe] Creating payment intent...', {
        cart: cart.length + ' items',
        amountUSD: amountUSD
      });

      // Step 1: Create payment intent using unified endpoint with Stripe strategy
      const intentResponse = await fetch('../../api/payments/process-payment.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          payment_method: 'STRIPE',
          cart: cart.map(item => ({
            id: item.id,
            name: item.name,
            price: item.price,
            qty: item.qty
          })),
          shipping_info: {
            fullname: formData.get('fullname'),
            phone: formData.get('phone'),
            email: formData.get('email') || '',
            address: formData.get('address'),
            city: formData.get('city') || '',
            district: formData.get('district') || ''
          },
          note: formData.get('note') || '',
          total_amount: totals.total,
          shipping_fee: totals.shipping,
          shipping_carrier: formData.get('shipping_carrier') || 'GHN'
        })
      });

      const intentData = await intentResponse.json();
      
      if (!intentData.success) {
        throw new Error(intentData.message || 'Failed to create payment intent');
      }

      console.log('[Stripe] Payment intent created:', intentData.data.order_id);

      // Step 2: Confirm payment with Stripe
      const { error, paymentIntent } = await stripe.confirmCardPayment(
        intentData.data.client_secret,
        {
          payment_method: {
            card: cardElement,
            billing_details: {
              name: formData.get('fullname'),
              email: formData.get('email') || undefined,
              phone: formData.get('phone') || undefined,
              address: {
                line1: formData.get('address'),
                city: formData.get('city') || undefined,
                state: formData.get('district') || undefined
              }
            }
          }
        }
      );

      if (error) {
        console.error('[Stripe] Payment failed:', error);
        showMessage(`Thanh toán thất bại: ${error.message}`, 'danger');
        return false;
      }

      if (paymentIntent.status === 'succeeded') {
        console.log('[Stripe] Payment succeeded:', paymentIntent.id);
        
        // Show success message immediately
        showMessage('Thanh toán thành công! Đang chuyển hướng...', 'success');
        
        // Step 3: Confirm payment in our backend (webhook alternative)
        const confirmResponse = await fetch('../../api/payments/confirm-payment.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            payment_intent_id: paymentIntent.id,
            order_id: intentData.data.order_id
          })
        });

        const confirmData = await confirmResponse.json();
        
        if (confirmData.success) {
          // Clear cart
          window.Cart.clear();
          
          // Redirect to My Orders page
          window.location.href = 'orders.php?success=1&order_id=' + intentData.data.order_id;
          
          return true;
        } else {
          console.warn('[Stripe] Backend confirmation failed, but payment succeeded');
          // Still redirect to orders page since Stripe payment succeeded
          // Webhook will handle the backend update
          window.Cart.clear();
          window.location.href = 'orders.php?success=1&order_id=' + intentData.data.order_id;
          return true;
        }
      }

      return false;
    } catch (error) {
      console.error('[Stripe] Error:', error);
      showMessage('Có lỗi xảy ra: ' + error.message, 'danger');
      return false;
    }
  }

  // Form submission handler
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    console.log('[Checkout] Form submitted');
    
    // Check if Cart module is available
    if (!window.Cart || typeof window.Cart.read !== 'function') {
      console.error('[Checkout] Cart module not available!');
      showMessage('Lỗi: Module giỏ hàng chưa được tải. Vui lòng tải lại trang.', 'danger');
      return;
    }
    
    // Validate form
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const formData = new FormData(form);
    const paymentMethod = formData.get('payment');
    
    // Check cart
    const cart = window.Cart.read();
    console.log('[Checkout] Cart check:', cart);
    
    if (!cart || !cart.length) {
      console.error('[Checkout] Cart is empty:', cart);
      showMessage('Giỏ hàng trống! Vui lòng thêm sản phẩm trước.', 'danger');
      return;
    }
    
    console.log('[Checkout] Cart valid with', cart.length, 'items');

    setLoading(true);
    showMessage('');

    try {
      if (paymentMethod === 'STRIPE') {
        await processStripePayment(formData);
      } else {
        await processCODOrder(formData);
      }
    } catch (error) {
      console.error('[Checkout] Error:', error);
      showMessage('Đã xảy ra lỗi. Vui lòng thử lại.', 'danger');
    } finally {
      setLoading(false);
    }
  });

  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function() {
    console.log('[Stripe] Checkout page loaded');
    
    // Render checkout summary from cart
    if (window.Cart && typeof window.Cart.read === 'function') {
      renderCheckoutSummary();
    } else {
      console.error('[Stripe] Cart module not available');
    }
  });

  // Render checkout summary
  function renderCheckoutSummary() {
    console.log('[Checkout] Rendering checkout summary...');
    
    if (!window.Cart || typeof window.Cart.read !== 'function') {
      console.error('[Checkout] Cart module not available for rendering');
      return;
    }
    
    const cart = window.Cart.read();
    console.log('[Checkout] Rendering cart with', cart ? cart.length : 0, 'items');
    
    const list = document.getElementById('checkout-items');
    const sumSub = document.getElementById('checkout-subtotal');
    const sumShip = document.getElementById('checkout-shipping');
    const sumTotal = document.getElementById('checkout-total');
    const empty = document.getElementById('checkout-empty');
    const wrap = document.getElementById('checkout-summary-wrap');

    if (!list) return;

    list.innerHTML = '';
    
    if (!cart || !cart.length) {
      if (wrap) wrap.classList.add('d-none');
      if (empty) empty.classList.remove('d-none');
      return;
    }
    
    if (wrap) wrap.classList.remove('d-none');
    if (empty) empty.classList.add('d-none');

    // Format currency (USD)
    const fmtCurrency = (n) => {
      if (typeof n !== 'number' || isNaN(n)) return '$0.00';
      return '$' + n.toFixed(2);
    };

    // Render cart items
    for (const item of cart) {
      const li = document.createElement('li');
      li.className = 'list-group-item d-flex justify-content-between align-items-center py-2';
      // Prices are already in USD
      const priceUSD = item.price * item.qty;
      
      // Create image element if available
      const imgHtml = item.image 
        ? `<img src="${item.image}" alt="${item.name}" class="me-2" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">` 
        : '';
      
      li.innerHTML = `
        <div class="d-flex align-items-center flex-grow-1">
          ${imgHtml}
          <span class="me-2">${item.name} × ${item.qty}</span>
        </div>
        <strong class="text-nowrap">${fmtCurrency(priceUSD)}</strong>
      `;
      list.appendChild(li);
    }

    // Calculate totals
    const totals = window.Cart.getTotals(cart);
    if (sumSub) sumSub.textContent = fmtCurrency(totals.subtotal);
    if (sumShip) sumShip.textContent = fmtCurrency(totals.shipping);
    if (sumTotal) sumTotal.textContent = fmtCurrency(totals.total);
  }

  // Initialize on page load
  
})();
