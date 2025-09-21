        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Confirm actions
        function confirmAction(message = 'Are you sure?') {
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
    </script>
</body>
</html>