document.body.addEventListener("click", function (e) {
const btn = e.target.closest("[data-add-to-cart]");
if (btn) {
    setTimeout(() => window.location.href = "cart.php", 300);
}
});

