document.addEventListener("DOMContentLoaded", () => {
    console.log("DOM loaded - initializing search...");
    
    const searchInput = document.getElementById("search-input");
    const tableBody = document.getElementById("customer-table-body");

    if (!searchInput) {
        console.error("❌ Search input not found! Check if #search-input exists.");
        return;
    }
    
    if (!tableBody) {
        console.error("❌ Table body not found! Check if #customer-table-body exists.");
        return;
    }

    console.log("✅ Search elements found:", { searchInput, tableBody });

    let debounceTimer;

    searchInput.addEventListener("input", function() {
        clearTimeout(debounceTimer);
        
        const q = this.value.trim();
        console.log("Searching for:", q);
        
        debounceTimer = setTimeout(() => {
            if (q.length === 0) {
                // Reload the page to show all customers
                window.location.href = 'khach_hang.php';
                return;
            }
            
            // FIX: Correct path based on folder structure (php/search_kh.php)
            fetch(`php/search_kh.php?q=${encodeURIComponent(q)}`)
                .then(response => {
                    console.log("Response status:", response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    console.log("Received HTML:", html.length, "characters");
                    tableBody.innerHTML = html;
                    
                    // Hide pagination when searching
                    const pagination = document.querySelector('.card-footer');
                    if (pagination) {
                        pagination.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error("❌ Fetch error:", error);
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="11" class="text-center text-danger py-4">
                                <div>Error loading search results</div>
                                <small>${error.message}</small>
                            </td>
                        </tr>
                    `;
                });
        }, 300);
    });

    // Clear search when Escape is pressed
    searchInput.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
            this.value = "";
            window.location.href = 'khach_hang.php';
        }
    });
});