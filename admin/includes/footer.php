        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });


        // Initialize DataTables
        $(document).ready(function() {
            $('.data-table').DataTable({
                responsive: true,
                pageLength: 10,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Confirm delete actions
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }

        // Show loading spinner
        function showLoading() {
            const loading = document.createElement('div');
            loading.id = 'loading-spinner';
            loading.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
            loading.style.backgroundColor = 'rgba(0,0,0,0.5)';
            loading.style.zIndex = '9999';
            loading.innerHTML = '<div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>';
            document.body.appendChild(loading);
        }

        // Hide loading spinner
        function hideLoading() {
            const loading = document.getElementById('loading-spinner');
            if (loading) {
                loading.remove();
            }
        }

        // Prevent automatic scrolling to top when clicking sidebar links
        (function() {
            // Save scroll positions for both main page and sidebar
            let scrollTimeout;
            function saveScrollPositions() {
                const mainScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
                const sidebarScrollPosition = document.getElementById('sidebar').scrollTop;
                
                localStorage.setItem('mainScrollPosition', mainScrollPosition);
                localStorage.setItem('sidebarScrollPosition', sidebarScrollPosition);
            }
            
            // Save main page scroll position
            window.addEventListener('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    const mainScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
                    localStorage.setItem('mainScrollPosition', mainScrollPosition);
                }, 10);
            });
            
            // Save sidebar scroll position
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.addEventListener('scroll', function() {
                    clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(() => {
                        const sidebarScrollPosition = sidebar.scrollTop;
                        localStorage.setItem('sidebarScrollPosition', sidebarScrollPosition);
                    }, 10);
                });
            }
            
            // Handle sidebar navigation with scroll position preservation
            document.addEventListener('click', function(e) {
                const sidebarLink = e.target.closest('.sidebar-menu a');
                if (sidebarLink && sidebarLink.hasAttribute('data-page')) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Save current scroll positions
                    saveScrollPositions();
                    
                    // Navigate to the page
                    const page = sidebarLink.getAttribute('data-page');
                    if (page) {
                        window.location.href = page;
                    }
                }
            });
            
            // Restore scroll positions
            function restoreScrollPositions() {
                const savedMainPosition = localStorage.getItem('mainScrollPosition');
                const savedSidebarPosition = localStorage.getItem('sidebarScrollPosition');
                
                if (savedMainPosition && parseInt(savedMainPosition) > 0) {
                    window.scrollTo(0, parseInt(savedMainPosition));
                }
                
                if (savedSidebarPosition && parseInt(savedSidebarPosition) > 0) {
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar) {
                        sidebar.scrollTop = parseInt(savedSidebarPosition);
                    }
                }
            }
            
            // Multiple restoration attempts
            document.addEventListener('DOMContentLoaded', restoreScrollPositions);
            window.addEventListener('load', restoreScrollPositions);
            
            // Additional restoration after delays
            setTimeout(restoreScrollPositions, 100);
            setTimeout(restoreScrollPositions, 300);
            setTimeout(restoreScrollPositions, 600);
        })();
    </script>
</body>
</html>