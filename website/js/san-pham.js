document.addEventListener('DOMContentLoaded', () => {
  // Mở popup
  document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', () => {
      document.getElementById('product-popup').style.display = 'flex';
    });
  });

  // Đóng popup
  document.querySelector('.popup-back').addEventListener('click', () => {
    document.getElementById('product-popup').style.display = 'none';
  });
});
