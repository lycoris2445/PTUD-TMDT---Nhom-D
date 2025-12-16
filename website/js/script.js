// === JS: Bật/Tắt nội dung từng phần khi nhấn vào tiêu đề ===
document.addEventListener("DOMContentLoaded", function () {
  const blocks = document.querySelectorAll(".policy-block");

  blocks.forEach(block => {
    const header = block.querySelector("h3");
    const content = block.querySelector(".policy-content");

    // Ẩn toàn bộ nội dung khi load
    content.style.maxHeight = null;

    header.addEventListener("click", () => {
      // Nếu block đang mở → đóng lại
      if (block.classList.contains("active")) {
        block.classList.remove("active");
        content.style.maxHeight = null;
        return;
      }

      // Đóng tất cả block khác
      blocks.forEach(b => {
        b.classList.remove("active");
        b.querySelector(".policy-content").style.maxHeight = null;
      });

      // Mở block hiện tại
      block.classList.add("active");
      content.style.maxHeight = content.scrollHeight + "px";
    });
  });

  // Mobile nav toggle
  const navToggle = document.querySelector(".nav-toggle");
  const navMenu = document.querySelector(".nav-menu");

  const closeMenu = () => {
    document.body.classList.remove("nav-open");
    if (navToggle) navToggle.setAttribute("aria-expanded", "false");
  };

  if (navToggle && navMenu) {
    navToggle.addEventListener("click", () => {
      const isOpen = !document.body.classList.contains("nav-open");
      document.body.classList.toggle("nav-open", isOpen);
      navToggle.setAttribute("aria-expanded", String(isOpen));
    });

    navMenu.querySelectorAll("a").forEach(link => {
      link.addEventListener("click", () => {
        if (window.innerWidth <= 900) closeMenu();
      });
    });

    window.addEventListener("resize", () => {
      if (window.innerWidth > 900) closeMenu();
    });
  }
});