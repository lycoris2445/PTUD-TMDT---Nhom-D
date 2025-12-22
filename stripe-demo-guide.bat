@echo off
echo ====================================
echo   STRIPE PAYMENT - DEMO GUIDE
echo ====================================
echo.

echo Step 1: Start Stripe CLI
echo --------------------------
echo Run this command in a NEW terminal:
echo.
echo   stripe listen --forward-to localhost/PTUD%%%%20TMDT%%%%20-%%%%20Nhom%%%%20D/api/payments/stripe-webhook.php
echo.
echo You will see output like:
echo   ^> Ready! Your webhook signing secret is whsec_xxxxxxxxxxxxx
echo.
pause

echo.
echo Step 2: Update .env file
echo --------------------------
echo Copy the webhook secret (whsec_...) from Stripe CLI output
echo Open: .env
echo Update line: STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx
echo.
pause

echo.
echo Step 3: Test Payment Flow
echo --------------------------
echo 1. Open browser: http://localhost/PTUD%%%%20TMDT%%%%20-%%%%20Nhom%%%%20D/website/php/
echo 2. Login with test account
echo 3. Add products to cart
echo 4. Go to checkout
echo 5. Select "Thanh toan bang the (Stripe)"
echo 6. Enter test card:
echo    - Card: 4242 4242 4242 4242
echo    - Expiry: 12/34
echo    - CVC: 123
echo 7. Submit payment
echo 8. Watch webhook events in Stripe CLI terminal
echo.
pause

echo.
echo Step 4: Verify Order
echo --------------------------
echo 1. Check "My Orders" in navbar
echo 2. Find your order
echo 3. Status should be "on_hold" (payment confirmed)
echo 4. Payment status should be "paid"
echo.
pause

echo.
echo Step 5: Test Refund
echo --------------------------
echo 1. Click on your order
echo 2. Scroll to "Request Refund" section
echo 3. Enter refund amount (max = order total)
echo 4. Enter reason
echo 5. Submit refund request
echo 6. Watch webhook event: charge.refunded
echo 7. Refund status updates to "completed"
echo.
pause

echo.
echo ====================================
echo   TROUBLESHOOTING
echo ====================================
echo.
echo Problem: Webhook not receiving events
echo Solution: 
echo   - Check Stripe CLI is still running
echo   - Verify .env has correct webhook secret
echo   - Restart Apache after .env changes
echo.
echo Problem: Payment fails immediately
echo Solution:
echo   - Check Stripe keys in .env are correct
echo   - Verify using test mode keys (sk_test_...)
echo   - Check browser console for errors
echo.
echo Problem: Order status not updating
echo Solution:
echo   - Check Apache error log: C:\laragon\bin\apache\...\logs\error.log
echo   - Look for [STRIPE WEBHOOK] logs
echo   - Verify database connection
echo.
echo ====================================
echo   TEST CARDS
echo ====================================
echo.
echo Success: 4242 4242 4242 4242
echo Declined: 4000 0000 0000 0002
echo 3D Secure: 4000 0025 0000 3155
echo.
echo Expiry: Any future date (e.g., 12/34)
echo CVC: Any 3 digits (e.g., 123)
echo.
echo More cards: https://stripe.com/docs/testing#cards
echo.
pause
