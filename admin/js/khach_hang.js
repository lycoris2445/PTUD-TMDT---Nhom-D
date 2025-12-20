document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("search-input");
    const tableBody = document.getElementById("customer-table-body");
    const pagination = document.querySelector('.card-footer');

    if (!searchInput || !tableBody) {
        console.error("Không tìm thấy phần tử HTML cần thiết!");
        return;
    }

    let debounceTimer;

    searchInput.addEventListener("input", function() {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        
        debounceTimer = setTimeout(() => {
            if (q.length === 0) {
                // Nếu xóa trắng ô search, tải lại trang để hiện đầy đủ
                window.location.reload();
                return;
            }
            
            // Fetch dữ liệu từ search_kh.php (file này cùng thư mục với customer.php)
            fetch(`search_kh.php?q=${encodeURIComponent(q)}`)
                .then(response => {
                    if (!response.ok) throw new Error('Kết nối server thất bại');
                    return response.text();
                })
                .then(html => {
                    tableBody.innerHTML = html;
                    if (pagination) pagination.style.display = 'none'; // Ẩn phân trang khi search
                })
                .catch(error => {
                    console.error("Lỗi:", error);
                    tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Lỗi tìm kiếm: ${error.message}</td></tr>`;
                });
        }, 300);
    });

    // Clear search when Escape is pressed
    searchInput.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
            this.value = "";
            window.location.href = 'customer.php';
        }
    });
});